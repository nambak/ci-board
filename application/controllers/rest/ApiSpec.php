<?php

defined('BASEPATH') or exit('No direct script access allowed');

class ApiSpec extends MY_RestController
{
    /**
     * 공통 스키마 파일 (먼저 로드됨)
     */
    private $schema_files = [
        '_schemas.json',
    ];

    /**
     * API 스펙 파일 목록
     */
    private $spec_files = [
        'board.json',
        'article.json',
        'comment.json',
        'auth.json',
        'user.json',
        'attachment.json',
    ];

    /**
     * 분리된 OpenAPI 스펙 파일들을 병합하여 반환
     * GET /rest/apispec
     */
    public function index_get()
    {
        $assets_path = FCPATH . 'assets/';

        // 기본 스펙 로드
        $base_file = $assets_path . '_base.json';
        if (!file_exists($base_file)) {
            return $this->response(['message' => '_base.json not found'], 404);
        }

        $base_spec = json_decode(file_get_contents($base_file), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->response(['message' => 'Invalid _base.json'], 500);
        }

        // _description 필드 제거 (메타데이터)
        unset($base_spec['_description']);

        // paths와 components 초기화
        $merged_paths = [];
        $merged_schemas = [];

        // 공통 스키마 파일 먼저 로드
        foreach ($this->schema_files as $file) {
            $file_path = $assets_path . $file;

            if (!file_exists($file_path)) {
                log_message('debug', "Schema file not found: {$file}");
                continue;
            }

            $spec = json_decode(file_get_contents($file_path), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                log_message('error', "Invalid JSON in {$file}: " . json_last_error_msg());
                continue;
            }

            // components/schemas 병합
            if (isset($spec['components']['schemas'])) {
                $merged_schemas = array_merge($merged_schemas, $spec['components']['schemas']);
            }
        }

        // 각 도메인 스펙 파일 병합
        foreach ($this->spec_files as $file) {
            $file_path = $assets_path . $file;

            if (!file_exists($file_path)) {
                log_message('error', "API spec file not found: {$file}");
                continue;
            }

            $spec = json_decode(file_get_contents($file_path), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                log_message('error', "Invalid JSON in {$file}: " . json_last_error_msg());
                continue;
            }

            // _metadata 필드 제거
            unset($spec['_metadata']);

            // paths 병합
            if (isset($spec['paths'])) {
                foreach ($spec['paths'] as $path) {
                    if (isset($merged_paths[$path])) {
                        log_message('error', "Path conflict detected: {$path} in {$file}");
                    }
                }

                $merged_paths = array_merge($merged_paths, $spec['paths']);
            }

            // components/schemas 병합
            if (isset($spec['components']['schemas'])) {
                foreach ($spec['components']['schemas'] as $schema_name) {
                    if (isset($merged_schemas[$schema_name])) {
                        log_message('error', "Schema conflict detected: {$schema_name} in {$file}");
                    }
                }

                $merged_schemas = array_merge($merged_schemas, $spec['components']['schemas']);
            }
        }

        // 최종 스펙 구성
        $base_spec['paths'] = $merged_paths;

        if (!empty($merged_schemas)) {
            if (!isset($base_spec['components'])) {
                $base_spec['components'] = [];
            }
            $base_spec['components']['schemas'] = $merged_schemas;
        }

        // JSON 응답 반환
        $this->response($base_spec, 200);
    }
}
