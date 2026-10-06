# 관리자 대리 리뷰 · 텔레그램 운영 알림

## 저장소와 배포 브랜치 확인 결과

- 개발 저장소: `/Users/moosugkim/Projects/redtoy-git`, 웹 루트 `www/`.
- 검토 브랜치: `codex/admin-reviews-telegram`.
- 운영 기준 브랜치: `codex/rollback-pg-keep-selected`, 기준 커밋 `1e0ffbe10a184523207d8450d53394aad462e994`.
- 사용자가 제공한 운영 SSH 출력과 GitHub 원격 조회에서 기준 브랜치와 커밋이 일치함을 확인했다. 운영 웹 루트는 `/home/user/redtoy/www`이다.
- 사용자가 제공한 Apache 설정: PHP 8.4 모듈, `short_open_tag=On`. CLI는 PHP 8.4.15이며 curl, mbstring, mysqli, openssl 확장이 있다. 실제 웹 요청에서의 확장/설정 및 PHP 8.4 전체 실행 검증은 별도 필요하다.
- 로컬 main 전체를 병합하지 않았다. 운영 커밋에서 별도 작업 폴더를 만들어 이번 기능만 적용했다. 기존 로컬 포인트 정책 변경과 운영 `www/data/`는 포함하지 않았다.
- 참고 저장소의 로컬 원격 추적 ref `origin/miyamall-domain-cleanup`에서 리뷰 날짜 및 텔레그램 구현을 읽었다. 도메인·토큰·Chat ID·환경설정을 복사하지 않았다.
- 사용자 승인 범위는 별도 검토 브랜치의 커밋·push까지다. 운영 브랜치 병합, 서버 파일/설정 변경, DB 적용, 실제 API 전송은 수행하지 않는다.

## 리뷰 동작

`buzinga` PC·모바일 상품상세 작성/수정 폼에서 최고관리자 또는 사용후기 관리 메뉴 `400650` 쓰기 권한 보유자가 고객 닉네임과 오늘/과거 날짜를 입력한다. 대리 등록 여부는 DB에 보존하되 사용자 요청에 따라 화면 안내 문구는 표시하지 않는다. 기존 일반 리뷰를 관리자 수정으로 대리 리뷰로 바꾸지는 않는다.

- 기존 `is_name`, `is_time`에 표시 닉네임과 지정 등록일을 저장한다.
- `mb_id`는 실제 등록 관리자 계정이다. 고객 계정은 생성하지 않는다.
- `is_provided=1`, `is_registered_by`, `is_registered_at`에 상품 제공 여부와 실제 최초 등록자·시각을 보존한다. 수정해도 최초 등록 감사 정보는 바뀌지 않는다.
- 대리 리뷰의 `ct_id=0`으로 구매 연결·리뷰 포인트 지급을 하지 않는다. 관리자 승인/일괄 승인에서도 대리 리뷰에는 포인트를 지급하지 않는다.
- 일반 회원이 닉네임·날짜·상품 제공 값을 조작해도 회원명·현재 시각·일반 리뷰로 저장된다. 일반 회원은 대리 리뷰를 수정할 수 없다.
- 생성/수정은 세션 CSRF 토큰, 서버 권한, 실재 상품, 별점 1~5, 닉네임 1~50자, 실제 존재하는 날짜를 검증한다. 기존 기본/모바일/기본 테마 폼에도 일반 리뷰 제출용 CSRF 토큰을 추가했다.
- 본문은 기존 HTML 정화 함수를 통과한다. 파일 업로드 전에 권한과 CSRF를 검증하며 이미지 형식과 10MB 제한을 확인한다. 별점·제목·기존 사진 입력을 유지한다.
- 목록·상세·AJAX 응답에서 대리 등록 안내 문구를 표시하지 않는다. 모바일은 대리 리뷰에 한해 회원 닉네임 조회/이름 마스킹 대신 지정 닉네임을 쓴다.
- 구매 인증 표시를 새로 만들지 않았다. 관리자 대리 등록 사실을 구매 인증으로 취급하지 않는다. 전체 평점 제목도 구매자라고 단정하지 않도록 수정했다.
- 최신순 기본 정렬은 지정한 `is_time DESC, is_id DESC`이며 별점 평균·노출 건수는 기존 집계 함수를 호출한다. 승인 대기 설정도 유지한다.

## 주문·문의 흐름과 연결 지점

| 이벤트/진입 경로 | 저장 완료 이후 연결 위치 |
| --- | --- |
| PC/모바일 주문, 즉시 PG 승인 | `shop/ordermail1.inc.php` 시작 부분. 이메일 사용 여부 검사/실제 발송 전이며 주문·장바구니 저장 및 실패 처리 이후 호출됨 |
| Mainpay PC/모바일 및 Payster | 같은 공통 include. PG 라이브러리를 수정하지 않고 포함 |
| 관리자 미완료 주문 복원 | `adm/shop_admin/inorderformupdate.php` 최종 주문 금액·상태 저장 후 |
| 무통장 일괄 입금 확인 | `adm/shop_admin/orderlistupdate.php`의 `change_status` 및 `order_update_receipt` 후 |
| 관리자 주문 상세의 입금액/수동 처리 | `adm/shop_admin/orderformreceiptupdate.php` DB·장바구니 갱신 후, 메일/SMS 전 |
| KCP·이니시스·LG 가상계좌 입금 통보 | `shop/settle_*_common.php`의 미수금/입금 상태·장바구니 저장 블록 뒤 |
| 기존 주문에 연결된 개인결제 완료 | PC/모바일 `personalpayformupdate.php` 미수금 및 장바구니 갱신 뒤 |
| 1:1문의 | 기존 `qawrite_update` 훅. 신규/추가 문의만 기록 |
| 1:1문의 답변 | 같은 훅. 실제 저장된 답변과 원 문의 ID를 확인. 수정(`w=u`)은 제외 |

주문번호만 생성된 상태에서는 알림이 생기지 않는다. DB에서 주문 및 주문에 연결된 저장된 장바구니를 재확인한다. 결제 완료는 주문 상태가 `입금/준비/배송/완료`이고 미수금이 0 이하인 경우에만 기록한다. `취소` 또는 미수금이 남은 주문은 결제 완료가 아니다. 즉시 결제 주문은 주문 접수와 결제 완료 두 이벤트가 각각 한 번 생긴다.

주문 알림에는 번호, 첫 상품명, 결제금액(접수 시 결제할 금액/완료 시 수납액), 결제수단, 저장된 처리 시각, 관리자 상세 링크가 들어간다. 문의에는 번호·제목·시각·상세 링크만 들어간다. 그누보드는 관리자도 `bbs/qaview.php`에서 문의를 확인하므로 이 권한 확인 화면으로 연결한다. 연락처·주소·본문·회원 정보를 조회/포함하지 않으며 제목에 들어간 휴대폰 번호·이메일도 정리한다. 제목에 사용자가 직접 적은 다른 형태의 개인정보까지 의미적으로 판별하지는 않는다.

## 큐, 중복과 실패

- HTTP 요청에서는 DB 큐 적재만 한다. Telegram HTTP 요청은 CLI 작업자에서만 실행한다.
- `UNIQUE(event_key)`가 `order_created:주문번호`, `order_paid:주문번호`, `qa_created:문의번호`, `qa_answered:원문의번호` 중복을 차단한다. 재요청은 기존 메시지·결과·재시도 상태를 덮어쓰지 않는다.
- 답변 수정·같은 문의의 답변 삭제 후 재등록도 최초 답변 알림을 추가하지 않는다.
- 큐 상태는 `pending/sending/sent/failed/uncertain`이다. MySQL 연결 단위 잠금으로 같은 DB 서버의 작업자 전송을 직렬화한다.
- 확정 실패는 60초부터 지수 지연, 최대 5회 시도한다. Telegram 429의 `retry_after`를 존중한다. 시도 한도에 도달하면 관리자 재시도가 필요하다.
- 연결 2초/전체 5초 제한은 CLI에만 적용한다. 전송 실패/지연은 주문·결제·문의 저장을 롤백하지 않는다. 큐 DB 저장 실패도 예외를 외부로 전달하지 않고 고정 오류 코드만 서버 오류 로그에 기록한다.
- 큐 DB 자체가 사용 불가능하면 그 시점 이벤트는 큐에 남기지 못할 수 있다. 운영에서 고정 코드 `redtoy_telegram: queue_write_failed` 등의 오류 로그를 감시해야 한다. 결제 데이터를 변경하는 자동 재처리는 하지 않는다.
- **전송 여부 불명은 자동 재시도하지 않는다.** 타임아웃, 해석할 수 없는 응답, 메시지 수락 직후 프로세스 종료는 Telegram에서 이미 전송됐을 수 있다. 관리자에서 채널을 확인하고 중복 가능성에 동의해야 재시도할 수 있다. Telegram `sendMessage`에는 요청별 멱등성 키가 없어 네트워크 장애까지 포함한 절대적인 exactly-once 전송은 보장할 수 없다. [공식 Bot API](https://core.telegram.org/bots/api#sendmessage)
- 전송 성공한 중복 키를 삭제하면 과거 이벤트가 다시 적재될 수 있다. 운영 큐 정리 시 성공 키를 보존해야 한다.

## DB 적용 방법

별도 파일 `redtoy-review-telegram-migration.sql.example`을 배포 담당자가 검토해 수동 적용한다. 대상은 **레드토이 개발/스테이징 DB에서 먼저 검증한 뒤 운영 레드토이 DB**이며 실제 DB 이름과 접두사는 운영 담당자가 확인한다. SQL은 기본 접두사 `g5_` 예시다. 운영 DB 적용은 자동 실행하지 않는다.

1. 실제 배포 버전, DB 제품/버전, 테이블 접두사, 현재 리뷰 컬럼과 인덱스를 확인한다. 이미 적용된 마이그레이션을 재실행하지 않는다. 일부만 적용됐으면 스키마를 비교해 누락된 문장만 적용한다.
2. 배포 직전 파일을 안전한 외부 백업 위치에 보관한다. DB는 복원 검증 가능한 일관된 스냅샷/백업을 확보한다. 기존 리뷰/상품 집계와 새 설정·큐가 이미 있다면 이를 포함한다. 백업·운영 데이터·키를 이 저장소에 넣지 않는다.
3. 스테이징에서 리뷰 테이블에 컬럼 3개와 `(it_id,is_confirm,is_time,is_id)` 인덱스를 추가하고, 설정/큐 테이블 2개를 만든다. 기존 데이터는 삭제/백필하지 않는다. 기존 리뷰의 상품 제공 값은 0, 감사 값은 빈 문자열/NULL이다.
4. 큰 리뷰 테이블의 `ALTER TABLE`은 DB 버전·엔진에 따라 테이블 재작성/메타데이터 잠금이 발생한다. 복제본에서 소요 시간과 잠금 영향을 측정하고 유지보수 시간에 적용한다. `LOCK=NONE`을 지원한다고 가정하지 않는다.
5. **스키마를 먼저, 코드 파일들을 다음에 일괄 적용**한다. 웹 요청에서 CREATE/ALTER를 실행하는 신규 코드는 없다. 새 기능 파일 일부만 배포하면 폼/함수 불일치가 발생하므로 파일 목록 전체를 확인한다.
6. 설정은 기본 비활성이다. 아래 키·cron 설정과 스테이징 검증이 끝난 뒤 이벤트를 활성화한다. 활성화 이전 과거 주문/문의 전체를 소급 알림하지 않는다.

검증 예시(읽기 전용, 접두사 변경 시 함께 수정):

```sql
SHOW COLUMNS FROM g5_shop_item_use LIKE 'is_provided';
SHOW COLUMNS FROM g5_shop_item_use LIKE 'is_registered_%';
SHOW INDEX FROM g5_shop_item_use WHERE Key_name='redtoy_review_date';
SELECT COUNT(*) FROM g5_shop_item_use;
SELECT enabled, order_created, order_paid, qa_created, qa_answered
FROM g5_redtoy_telegram_config WHERE id=1;
SELECT status, COUNT(*) FROM g5_redtoy_telegram_queue GROUP BY status;
SHOW INDEX FROM g5_redtoy_telegram_queue WHERE Key_name='redtoy_event_once';
```

리뷰 행 수를 적용 전과 비교한다. 설정 조회에서 `token_cipher`와 실제 토큰을 출력하지 않는다.

## 배포 후 설정

- CLI 작업자는 웹 common.php를 불러오지 않고 DB 설정과 알림 라이브러리만 읽는다. 웹 도메인 리다이렉트·세션·방문 기록·자동 DB 최적화·extend 훅을 실행하지 않는다. 로컬 PHP 8.4/MariaDB에서 합성 토큰과 임시 DB로 `--check` 성공 및 알림 비활성 `--run`의 `Processed: 0`을 확인했다. 외부 전송 함수는 차단한 상태로 검증했다.

- PHP 7.4 이상, `short_open_tag=On`(기존 테마 요구), OpenSSL AES-256-GCM, cURL, mysqli 필요. 신규 패키지를 설치하지 않았다.
- 서버의 안전한 비밀 설정에서 32바이트 난수의 Base64 값을 `REDTOY_TELEGRAM_KEY`로 등록한다. 웹 PHP와 CLI cron에 **같은 키**를 주입한다. 키를 Git, 웹 루트, 명령행 인자, 로그에 넣지 않는다. 암호화 키 백업도 별도 보관한다. 키를 바꿀 때는 기존 토큰을 새 키로 다시 등록해야 한다.
- 최고관리자로 `쇼핑몰관리 → 텔레그램 운영 알림` 또는 `/adm/shop_admin/redtoy_telegram.php`에 접근한다. 봇 토큰·수신 Chat ID·이벤트별 설정을 저장한다. 토큰 입력란은 항상 비어 있고 등록 여부만 표시한다.
- 사용자가 제공할 값: **봇 토큰**, **수신 Chat ID**. 채팅 응답이나 코드 파일에 적지 말고 배포 후 관리자 화면에 직접 입력한다. 봇의 대상 채팅 전송 권한도 확인한다.
- 먼저 `php -d short_open_tag=1 /선택한_배포경로/www/adm/shop_admin/redtoy_telegram_worker.php --check`를 실행한다. 이 명령은 메시지를 전송하지 않는다. 확인된 운영 웹 루트는 `/home/user/redtoy/www`이므로 작업자 경로는 `/home/user/redtoy/www/adm/shop_admin/redtoy_telegram_worker.php`이다.
- 기존 주문 자동 완료/포인트 CLI 작업과 같은 운영 cron 관리 방식에 다음 작업을 추가한다. 여기서 실제 cron 등록/서버 변경을 수행하지 않았다.

```cron
* * * * * /검증한/PHP경로 -d short_open_tag=1 /실제/웹루트/adm/shop_admin/redtoy_telegram_worker.php --run
```

1회 최대 10건을 처리한다. 평상시 지연은 cron 주기와 큐 길이에 따른다. 키는 cron 서비스 환경으로 주입하며 위 명령행에 쓰지 않는다. 오류 로그와 큐 대기/실패/불명 건수를 확인한다. 이벤트 또는 전체 알림을 끄면 이미 대기 중인 해당 전송도 멈춘다. 큐는 적재 당시 Chat ID를 보존하므로 수신처 변경 전 대기 큐를 확인한다.

테스트 버튼은 “저장된 실제 채널로 전송” 확인란을 요구한다. 확인 후 테스트도 큐에 적재되고 CLI에서 전송된다. 이번 작업에서는 테스트 버튼·운영 채널 전송을 실행하지 않았다. 처음에는 별도 테스트 봇/채팅으로 검증한 뒤 운영 수신처를 설정한다.

## 검증 결과 및 남은 검증

- 로컬 HTTP 검증: PHP 8.4.24 임시 서버에서 실제 PC·모바일 리뷰 폼 렌더링을 확인하고, 테스트 세션/SQLite와 실제 저장 컨트롤러·업로드 함수·평점 집계 함수를 연결해 18개 검증 통과. 합성 PNG의 multipart 업로드, 잘못된 이미지 거부, 관리자 등록/수정, 일반 회원 필드 위조 차단, CSRF 거부, 리뷰 수/평점 집계를 확인했다. 인증·구매 확인·HTML 정화는 테스트 대역이므로 실제 로그인/전체 사이트 통합 테스트로 간주하지 않는다.
- 사진 업로드 중 PHP 8.4 경고를 발견하여 코어 `www/lib/common.lib.php`의 확장자 추출 1줄을 `pathinfo`로 교체했다. 임시 배열을 참조 인수로 넘기던 경고를 제거하며 허용 확장자와 업로드 경로는 유지한다. 롤백은 해당 한 줄 변경을 되돌리는 것이다.

- `php -d short_open_tag=1 tests/redtoy_features.php`: 78개 오프라인 검증 통과. 실제 리뷰 등록/수정 처리 파일과 관리자 수정 파일을 실행했다. 권한/CSRF/위조 필드/날짜/감사 정보/집계 호출, 네 이벤트, 중복, 사용 설정, 재시도/한도/백오프/응답 유실/작업자 중단/토큰 암호화, 기존 주문 생성 8경로·결제/관리자 8경로 연결을 검사한다.
- 추가 로컬 검증: PHP 8.4.24 + MariaDB 12.3.2의 임시 소켓 전용 DB에서 84개 검증 통과. 실제 마이그레이션 SQL로 기존 리뷰 보존·기본값·알림 비활성을 확인했고, 애플리케이션 SQL을 변환 없이 실행하여 고유키 중복 방지와 두 DB 연결 간 작업자 잠금을 확인했다. Telegram 전송은 모의 함수이며 curl_exec를 비활성화했다. 테스트 DB는 실행 종료 시 삭제한다. 전체 사이트/외부 HTTP 통합 검증은 아니다.
- 재현: 별도로 초기화한 임시 MariaDB를 TCP 없이 실행한 후 `REDTOY_TEST_SOCKET=/private/tmp/redtoy-local-db.XXXXXX/db.sock /opt/homebrew/opt/php@8.4/bin/php -d short_open_tag=1 -d disable_functions=curl_exec tests/redtoy_features.php`. 실제 임시 소켓 경로로 바꾼다. 환경변수를 생략하면 기존 SQLite 78개 검증을 실행한다. 운영 DB 설정은 읽지 않는다.
- 이 검토 브랜치의 기능 PHP 40개와 테스트 PHP 1개, 총 41개를 `php -d short_open_tag=1 -l`로 검사하여 모두 통과했다. PHP 8.2.33에 이어 로컬 PHP 8.4.24에서도 모두 통과했다. 확인된 운영 PHP 8.4에서의 전체 사이트 실행 검증은 별도 필요하다.
- `git diff --check`는 기존 CRLF를 보존한 줄을 trailing whitespace로 판정한다. `git -c core.whitespace=blank-at-eol,blank-at-eof,space-before-tab,cr-at-eol diff --check`는 통과한다. 저장소 줄바꿈이나 Git 설정을 바꾸지 않았다.
- `git status --short`, `git diff --stat`, 변경 파일 diff를 확인했다.
- 전체 사이트 스테이징 웹 환경과 테스트용 PG 계정·키가 없어 실제 로그인부터 이어지는 전체 PC·모바일 화면/업로드 흐름과 결제/콜백/메일·알림톡을 검증하지 않았다. 위 로컬 폼·HTTP 검증은 이 전체 흐름 검증과 별개다. 운영 설정을 이용한 로컬 사이트 부팅도 하지 않았다.

운영 반영 전 스테이징에서 관리자/일반 회원의 새 리뷰·수정·사진·승인·표시·최신순·평점, 미결제/즉시결제/무통장 수동 확인/가상계좌/개인결제, 문의/답변/수정, 콜백 재전송, 큐 작업자 동시 실행, 네트워크 차단 시 원 업무 성공을 확인한다. PG 테스트는 별도 승인과 테스트 키로만 수행한다. 운영 PG 설정·콜백 URL·세션·회원가입·성인인증·배송비/쿠폰/결제 계산식은 이번 변경으로 수정하지 않았다.

## 롤백

1. 전체 알림을 끄고 cron 작업자를 중지한다. 큐와 오류 상태를 보존한다.
2. 새 코드에서 생성한 상품 제공 리뷰를 별도로 백업하고 숨김 처리할지 결정한다. 구 버전에는 상품 제공 안내가 없으므로 대리 리뷰가 공개된 채 안내 없는 구 코드로 돌아가지 않도록 한다. 숨김 처리 시 기존 `is_confirm` 값과 상품별 평점/건수를 백업하고 기존 집계 함수로 재계산한다. 운영 데이터 변경은 배포 담당자가 검토·수행한다.
3. **작업 시작 전 파일 백업으로 이번 기능 변경만 되돌린다.** 이 검토 브랜치의 기준은 운영 커밋 `1e0ffbe`이다. 배포 후 추가 변경이 있다면 전체 reset 대신 이번 기능만 되돌려야 한다. `orderlistupdate.php`에서는 새 알림 호출만 제거한다.
4. 추가 DB 컬럼/테이블은 우선 남겨 둔다. 구 코드와 호환되며 감사 정보·중복 키·실패 내역을 보존한다. 즉시 DROP은 필요하지 않다. 코드 재적용 시 남은 스키마를 재사용하고 마이그레이션을 중복 실행하지 않는다.
5. 영구 철회 후에만 백업/보존 기간과 리뷰 공개 정책을 정하고 추가 컬럼/인덱스/테이블 제거 여부를 별도 승인한다. 이미 발생한 주문·결제 데이터를 이 기능 롤백을 이유로 되돌리지 않는다.

## 변경 파일

### 신규 파일

- `www/lib/redtoy_review.lib.php`: 리뷰 권한·날짜/닉네임·CSRF·입력 필드·안내 표시.
- `www/extend/redtoy_review.extend.php`: 공통 라이브러리 로딩.
- `www/lib/redtoy_telegram.lib.php`: 암호화, 이벤트 생성, 큐 적재, 실패 분류, 전송 작업자.
- `www/extend/redtoy_telegram.extend.php`: 문의 이벤트와 최고관리자 메뉴 연결.
- `www/adm/shop_admin/redtoy_telegram.php`: 설정·테스트 예약·결과/실패·재시도 화면.
- `www/adm/shop_admin/redtoy_telegram_worker.php`: CLI 전송/설정 검사.
- `tests/redtoy_features.php`: 운영 접속 없는 검증.
- `docs/redtoy-review-telegram-migration.sql.example`: 별도 DB 변경 예시.
- `docs/redtoy-review-telegram.md`: 이 문서.

### 기존 파일 수정 — 코어 포함

다음 shop/mobile/adm 기본 처리 파일은 **영카트 코어 기반 파일**이다. 리뷰 저장 및 주문 성공 이벤트에 필요한 훅이 없어 최소한의 처리/큐 호출을 연결했다. 업데이트 시 아래 변경을 비교 병합해야 한다. 문의 본체는 기존 훅을 사용해 수정하지 않았다.

- `www/shop/itemuseformupdate.php`: 대리 저장/수정, 권한·CSRF·입력/이미지 검증, 포인트 방지.
- `www/shop/itemuseform.php`, `www/mobile/shop/itemuseform.php`: 리뷰 관리자 권한 일치 및 구매 조건 우회.
- `www/shop/itemuse.php`, `www/mobile/shop/itemuse.php`, `www/shop/itemuselist.php`, `www/mobile/shop/itemuselist.php`: 등록일 최신순 기본 정렬.
- `www/shop/ajax.review.php`: 대리 닉네임·안내, 공개 범위 확인, JSON·빈 이미지 배열.
- `www/adm/shop_admin/itemuseform.php`, `www/adm/shop_admin/itemuseformupdate.php`: 관리자 수정 입력·저장·검증.
- `www/adm/shop_admin/itemuselistupdate.php`: 대리 리뷰 일괄 승인 포인트 방지.
- `www/shop/ordermail1.inc.php`: 생성 성공 공통 큐 연결.
- `www/adm/shop_admin/inorderformupdate.php`, `www/adm/shop_admin/orderformreceiptupdate.php`, `www/adm/shop_admin/orderlistupdate.php`: 관리자 처리 성공 큐 연결.
- `www/shop/settle_kcp_common.php`, `www/shop/settle_inicis_common.php`, `www/shop/settle_lg_common.php`: 가상계좌 입금 큐 연결.
- `www/shop/personalpayformupdate.php`, `www/mobile/shop/personalpayformupdate.php`: 주문 연결 개인결제 큐 연결.
- `www/skin/shop/basic/itemuseform.skin.php`, `www/mobile/skin/shop/basic/itemuseform.skin.php`, `www/theme/basic/skin/shop/basic/itemuseform.skin.php`, `www/theme/basic/mobile/skin/shop/basic/itemuseform.skin.php`: 기본 스킨 일반 리뷰 CSRF 호환.

커스텀 테마 파일:

- `www/theme/buzinga/skin/shop/basic/itemuseform.skin.php`: 대리 등록 입력.
- `www/theme/buzinga/skin/shop/basic/itemuse.skin.php`, `www/theme/buzinga/skin/shop/basic/itemuselist.skin.php`: 상품 제공 안내.
- `www/theme/buzinga/mobile/skin/shop/basic/itemuseform.skin.php`, `www/theme/buzinga/mobile/skin/shop/basic/itemeditform.skin.php`: 모바일 등록/수정 입력과 권한.
- `www/theme/buzinga/mobile/skin/shop/basic/itemuse.skin.php`, `www/theme/buzinga/mobile/skin/shop/basic/itemuselist.skin.php`: 목록 이름·안내.
- `www/theme/buzinga/mobile/skin/shop/basic/itemusedetail.skin.php`, `www/theme/buzinga/mobile/skin/shop/basic/item.form.skin.php`, `www/theme/buzinga/mobile/shop/mypage4.php`: 상세 팝업 JSON 호환·닉네임 안전 출력·안내.

원래 개발 작업 폴더의 기존 변경은 사용자 작업으로 보존했으며 이 검토 브랜치에는 가져오지 않았다. `configform.php`, `itemform.php`, `orderform.php`, `shop/orderformupdate.php`의 기존 diff 및 `redtoy_order.lib.php`, `redtoy-point-policy-migration.sql.example`은 이번 기능의 변경 목록에 포함하지 않는다. 이 브랜치의 `orderlistupdate.php`는 운영 원본에 큐 호출 한 줄만 추가했다.
