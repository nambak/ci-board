# 보안 강화 문서: 사용자 열거 공격 방지

## 개요

이 문서는 `check_email_get` 메서드의 사용자 열거 공격 취약점을 해결하기 위해 구현된 보안 조치들을 설명합니다.

## 보안 취약점

**기존 문제점:**
- `check_email_get` 메서드가 이메일 존재 여부를 공개적으로 노출
- 공격자가 유효한 사용자 이메일 목록을 수집할 수 있는 위험성
- Rate limiting, CAPTCHA, 인증 등의 보안 조치 부재

## 구현된 보안 조치

### 1. Rate Limiting (속도 제한)

**구현 위치:** `application/libraries/Rate_limiter.php`

**기능:**
- IP별 요청 빈도 제한 (5분간 5회)
- 슬라이딩 윈도우 알고리즘 사용
- 다양한 액션별 개별 제한 설정 가능

**설정:**
```php
// 이메일 체크: 5분간 5회
$rate_check = $this->rate_limiter->is_allowed($client_ip, 'email_check', 5, 300);

// 회원가입 토큰 생성: 10분간 3회
$rate_check = $this->rate_limiter->is_allowed($client_ip, 'signup_token', 3, 600);

// CAPTCHA 생성: 5분간 10회
$rate_check = $this->rate_limiter->is_allowed($client_ip, 'captcha_gen', 10, 300);
```

### 2. CAPTCHA 검증

**구현 위치:** `application/libraries/Simple_captcha.php`

**기능:**
- 수학 문제 기반 CAPTCHA 생성
- 세션 기반 답안 저장 및 검증
- 시도 횟수 제한 (3회)
- 자동 만료 (10분)

**사용법:**
```php
// CAPTCHA 생성
$captcha = $this->simple_captcha->generate();
// 반환: ['question' => '3 + 5 = ?', 'challenge_id' => 'abc123...']

// CAPTCHA 검증
$result = $this->simple_captcha->verify($challenge_id, $user_answer);
// 반환: ['valid' => bool, 'error' => string|null]
```

### 3. 회원가입 플로우 제한

**구현 위치:** `application/helpers/signup_security_helper.php`

**기능:**
- 회원가입 세션 토큰 생성 및 관리
- 이메일 체크 횟수 제한 (세션당 5회)
- 토큰 자동 만료 (30분)

**플로우:**
1. 회원가입 페이지 접근 시 토큰 생성
2. 이메일 체크 시 토큰 검증
3. 회원가입 완료 또는 세션 만료 시 토큰 무효화

### 4. 인증 사용자 권한

**기능:**
- 로그인된 사용자는 CAPTCHA 없이 이메일 체크 가능
- 의심스러운 활동 감지 시 CAPTCHA 요구
- 사용자별 개별 보안 설정 적용

### 5. 보안 로깅

**기능:**
- 모든 이메일 체크 시도 로깅
- Rate limit 위반 경고 로그
- CAPTCHA 실패 추적
- 보안 이벤트 상세 기록

## API 엔드포인트 변경사항

### 기존 엔드포인트

```
GET /rest/auth/check_email?email=test@example.com
```

**응답:**
```json
{
  "success": true,
  "exists": true,
  "message": "이미 사용 중인 이메일입니다."
}
```

### 보안 강화된 엔드포인트

```
GET /rest/auth/check_email?email=test@example.com&signup_token=xxx&captcha_id=yyy&captcha_answer=8
```

**성공 응답:**
```json
{
  "success": true,
  "exists": false,
  "message": "사용 가능한 이메일입니다.",
  "rate_limit": {
    "remaining": 4,
    "reset_time": 1625097600
  },
  "signup_flow": {
    "remaining_checks": 4
  }
}
```

**CAPTCHA 요구 응답:**
```json
{
  "success": false,
  "message": "보안을 위해 인증이 필요합니다.",
  "exists": false,
  "captcha_required": true,
  "captcha": {
    "question": "7 + 3 = ?",
    "challenge_id": "abc123..."
  },
  "error_code": "CAPTCHA_REQUIRED"
}
```

**Rate Limit 초과 응답:**
```json
{
  "success": false,
  "message": "요청이 너무 많습니다. 잠시 후 다시 시도해주세요.",
  "exists": false,
  "rate_limit": {
    "reset_time": 1625097600,
    "remaining": 0
  }
}
```

## 새로운 API 엔드포인트

### 1. 회원가입 토큰 생성

```
POST /rest/auth/generate_signup_token
```

**응답:**
```json
{
  "success": true,
  "message": "회원가입 세션이 시작되었습니다.",
  "signup_token": "abc123...",
  "expires_in": 1800,
  "email_checks_allowed": 5
}
```

### 2. CAPTCHA 생성

```
GET /rest/auth/generate_captcha
```

**응답:**
```json
{
  "success": true,
  "captcha": {
    "question": "4 * 2 = ?",
    "challenge_id": "def456..."
  },
  "expires_in": 600
}
```

## 클라이언트 구현 가이드

### 회원가입 플로우

1. **회원가입 페이지 로드 시:**
   ```javascript
   // 회원가입 토큰 생성
   const tokenResponse = await fetch('/rest/auth/generate_signup_token', {
     method: 'POST'
   });
   const tokenData = await tokenResponse.json();
   const signupToken = tokenData.signup_token;
   ```

2. **이메일 체크 시:**
   ```javascript
   async function checkEmail(email) {
     let url = `/rest/auth/check_email?email=${email}&signup_token=${signupToken}`;
     
     // CAPTCHA 정보가 있다면 추가
     if (captchaId && captchaAnswer) {
       url += `&captcha_id=${captchaId}&captcha_answer=${captchaAnswer}`;
     }
     
     const response = await fetch(url);
     const data = await response.json();
     
     if (data.captcha_required) {
       // CAPTCHA 표시
       showCaptcha(data.captcha);
       return;
     }
     
     if (response.status === 429) {
       // Rate limit 처리
       showRateLimitError(data);
       return;
     }
     
     // 정상 응답 처리
     handleEmailCheckResult(data);
   }
   ```

3. **CAPTCHA 처리:**
   ```javascript
   function showCaptcha(captcha) {
     document.getElementById('captcha-question').textContent = captcha.question;
     document.getElementById('captcha-id').value = captcha.challenge_id;
     document.getElementById('captcha-form').style.display = 'block';
   }
   ```

## 보안 설정

### Rate Limiting 설정

`application/libraries/Rate_limiter.php`에서 수정 가능:

```php
// 기본 설정
protected $default_max_requests = 5;    // 최대 요청 수
protected $default_time_window = 300;   // 시간 윈도우 (초)
```

### CAPTCHA 설정

`application/libraries/Simple_captcha.php`에서 수정 가능:

```php
// CAPTCHA 만료 시간 (초)
if (time() - $challenge['created_at'] > 600) // 10분

// 최대 시도 횟수
if ($captcha_data[$challenge_id]['attempts'] > 3) // 3회
```

### 회원가입 토큰 설정

`application/helpers/signup_security_helper.php`에서 수정 가능:

```php
// 토큰 만료 시간 (초)
if (time() - $token_data['created_at'] > 1800) // 30분

// 이메일 체크 제한 횟수
if ($token_data['email_checks_count'] >= 5) // 5회
```

## 모니터링 및 로깅

### 보안 이벤트 로그

다음 이벤트들이 자동으로 로깅됩니다:

- 이메일 체크 시도 (성공/실패)
- Rate limit 위반
- CAPTCHA 실패
- 유효하지 않은 토큰 사용
- 브루트 포스 시도

### 로그 파일 위치

```
application/logs/log-YYYY-MM-DD.php
```

### 모니터링 권장사항

1. **Rate limit 위반 모니터링**
   - 특정 IP의 반복적인 위반 감지
   - 자동 IP 차단 고려

2. **CAPTCHA 실패율 모니터링**
   - 높은 실패율은 자동화 시도 의심
   - 추가 보안 조치 적용

3. **토큰 남용 모니터링**
   - 빠른 토큰 소비 패턴 감지
   - 의심스러운 회원가입 시도 추적

## 추가 보안 강화 방안

### 단기 개선사항

1. **IP 화이트리스트/블랙리스트**
   - 알려진 악성 IP 자동 차단
   - 신뢰할 수 있는 IP 화이트리스트

2. **지역 기반 제한**
   - 특정 국가/지역에서의 접근 제한
   - GeoIP 기반 필터링

3. **디바이스 핑거프린팅**
   - 브라우저 핑거프린트 추적
   - 의심스러운 디바이스 감지

### 장기 개선사항

1. **머신러닝 기반 탐지**
   - 비정상적인 패턴 자동 감지
   - 적응형 보안 임계값 조정

2. **외부 보안 서비스 연동**
   - reCAPTCHA v3 연동
   - 위협 인텔리전스 서비스 활용

3. **실시간 알림 시스템**
   - 보안 이벤트 실시간 알림
   - 관리자 대시보드 구축

## 성능 고려사항

### 캐싱 최적화

- 파일 기반 캐시 사용으로 메모리 사용량 최소화
- 자동 만료로 캐시 크기 제한
- 필요시 Redis/Memcached로 업그레이드 가능

### 응답 시간 최적화

- 비동기 로깅으로 응답 지연 최소화
- 캐시 히트율 최대화
- 불필요한 데이터베이스 쿼리 방지

### 확장성 고려

- 멀티 서버 환경 지원
- 로드 밸런서 호환성
- 수평 확장 가능한 구조

## 결론

구현된 보안 조치들을 통해 사용자 열거 공격을 효과적으로 방지할 수 있습니다. 모든 보안 기능은 설정 가능하며, 필요에 따라 추가 강화가 가능합니다.

**핵심 보안 효과:**
- ✅ 무차별적인 이메일 수집 방지
- ✅ 자동화된 공격 차단
- ✅ 정상 사용자 경험 유지
- ✅ 포괄적인 보안 로깅
- ✅ 확장 가능한 보안 구조