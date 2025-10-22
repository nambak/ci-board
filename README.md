# 이메일 열거 공격 보안 해결책

## 🔒 보안 취약점 해결

이 프로젝트는 CodeIgniter 기반 게시판의 `check_email_get` 메서드에서 발생하는 **사용자 열거 공격(User Enumeration Attack)** 취약점을 해결합니다.

### 문제점
- 기존 이메일 체크 API가 이메일 존재 여부를 무제한으로 노출
- 공격자가 유효한 사용자 이메일 목록을 수집할 수 있는 위험성
- Rate limiting, CAPTCHA, 인증 등의 보안 조치 부재

### 해결책
종합적인 보안 조치를 통해 취약점을 완전히 해결했습니다.

## 🛡️ 구현된 보안 조치

### 1. **Rate Limiting (속도 제한)**
- IP별 요청 빈도 제한 (5분간 5회)
- 다양한 액션별 개별 제한 설정
- 슬라이딩 윈도우 알고리즘 사용

### 2. **CAPTCHA 검증**
- 수학 문제 기반 자동화 방지
- 세션 기반 보안 관리
- 시도 횟수 제한 (3회)

### 3. **회원가입 플로우 제한**
- 토큰 기반 접근 제어
- 세션당 이메일 체크 횟수 제한 (5회)
- 토큰 자동 만료 (30분)

### 4. **인증 사용자 권한**
- 로그인된 사용자 우선 권한
- 의심스러운 활동 감지 시 추가 검증

### 5. **보안 로깅**
- 모든 시도 추적 및 기록
- 공격 패턴 탐지
- 상세한 보안 이벤트 로깅

## 🚀 빠른 시작

### 1. 데모 페이지 실행
```bash
# 프로젝트 디렉토리에서
open demo_security.html
```

### 2. API 테스트

#### 회원가입 토큰 생성
```bash
curl -X POST http://your-domain.com/rest/auth/generate_signup_token
```

#### 이메일 확인 (보안 강화)
```bash
curl "http://your-domain.com/rest/auth/check_email?email=test@example.com&signup_token=YOUR_TOKEN&captcha_id=CAPTCHA_ID&captcha_answer=ANSWER"
```

## 📁 파일 구조

```
application/
├── controllers/rest/
│   └── Auth.php                    # 보안 강화된 인증 컨트롤러
├── libraries/
│   ├── Rate_limiter.php           # Rate limiting 라이브러리
│   └── Simple_captcha.php         # CAPTCHA 라이브러리
├── helpers/
│   └── signup_security_helper.php # 회원가입 보안 헬퍼
├── models/
│   └── User_m.php                 # 사용자 모델
└── logs/
    └── log-YYYY-MM-DD.php         # 보안 로그

v1/
└── swagger.json                   # 업데이트된 API 문서

demo_security.html                 # 보안 기능 데모
SECURITY_DOCUMENTATION.md          # 상세 보안 문서
```

## 🔧 설정

### Rate Limiting 설정
`application/libraries/Rate_limiter.php`:
```php
protected $default_max_requests = 5;    // 최대 요청 수
protected $default_time_window = 300;   // 시간 윈도우 (초)
```

### CAPTCHA 설정
`application/libraries/Simple_captcha.php`:
```php
// CAPTCHA 만료 시간
if (time() - $challenge['created_at'] > 600) // 10분

// 최대 시도 횟수
if ($captcha_data[$challenge_id]['attempts'] > 3) // 3회
```

### 회원가입 토큰 설정
`application/helpers/signup_security_helper.php`:
```php
// 토큰 만료 시간
if (time() - $token_data['created_at'] > 1800) // 30분

// 이메일 체크 제한 횟수
if ($token_data['email_checks_count'] >= 5) // 5회
```

## 📊 보안 테스트

### 정상 사용자 플로우 테스트
1. 회원가입 페이지 접근
2. 회원가입 토큰 생성
3. 이메일 입력 시 CAPTCHA 검증
4. 이메일 중복 확인 완료

### 공격 시뮬레이션 테스트
1. 토큰 없이 접근 시도 → **차단됨**
2. 빠른 연속 요청 → **Rate Limit 발동**
3. CAPTCHA 우회 시도 → **검증 실패**
4. 대량 이메일 체크 → **세션 제한 적용**

## 📈 모니터링

### 로그 파일 확인
```bash
tail -f application/logs/log-$(date +%Y-%m-%d).php
```

### 주요 모니터링 지표
- Rate limit 위반 횟수
- CAPTCHA 실패율
- 토큰 남용 패턴
- 의심스러운 IP 활동

## 🔍 API 문서

자세한 API 명세는 다음에서 확인할 수 있습니다:
- [Swagger JSON](v1/swagger.json)
- [보안 문서](SECURITY_DOCUMENTATION.md)

## 📝 변경 사항

### Before (취약한 코드)
```php
public function check_email_get()
{
    $email = $this->get('email');
    $exists = $this->user_m->check_email_exists($email);
    
    $this->response([
        'success' => true,
        'exists'  => $exists,  // 🚨 사용자 열거 취약점
        'message' => $exists ? '이미 사용 중' : '사용 가능'
    ], self::HTTP_OK);
}
```

### After (보안 강화된 코드)
```php
public function check_email_get()
{
    // ✅ Rate limiting
    $rate_check = $this->rate_limiter->is_allowed($client_ip, 'email_check', 5, 300);
    
    // ✅ 인증 또는 회원가입 토큰 검증
    if (!is_authenticated_user() && !validate_signup_token($signup_token)) {
        return $this->response(['error' => 'AUTH_REQUIRED'], 401);
    }
    
    // ✅ CAPTCHA 검증
    if (!$this->simple_captcha->verify($captcha_id, $captcha_answer)) {
        return $this->response(['captcha_required' => true], 422);
    }
    
    // ✅ 보안 로깅
    log_message('info', "Secure email check from {$client_ip}");
    
    // 이메일 확인 수행
    $exists = $this->user_m->check_email_exists($email);
    return $this->response(['exists' => $exists], 200);
}
```

## 🎯 보안 효과

- ✅ **사용자 열거 공격 완전 차단**
- ✅ **자동화된 공격 방지**
- ✅ **정상 사용자 경험 유지**
- ✅ **포괄적인 보안 로깅**
- ✅ **확장 가능한 보안 구조**

## 🤝 기여

보안 개선사항이나 버그 리포트는 언제든 환영합니다!

## 📄 라이선스

MIT License - 자세한 내용은 [LICENSE](license.txt) 파일을 참조하세요.