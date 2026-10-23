# 사입제품관리 / 사입제품 주문 필터

## 기능과 권한

주문내역 검색 버튼 오른쪽에 `사입제품관리`와 `사입제품 주문건 보기 ON/OFF`를 추가한다. ON은 파란색과 aria-pressed로 표시하며 다시 누르면 OFF가 된다. 전환 시 검색·주문상태·결제수단·기타 체크박스·기간 조건은 유지하고 첫 페이지로 이동한다.

관리 화면은 주문관리 쓰기 권한을 요구하고 POST 저장 전에 관리자 CSRF 검사를 한다. 코드 입력은 영문·숫자·하이픈 1~20자, 공백/탭/줄바꿈 구분, 최대 500개 및 입력 32KiB로 제한한다. 대소문자 중복을 제거하고 실제 상품코드의 표기를 사용한다. 신규 등록 코드가 상품에 없으면 저장하지 않고 해당 코드를 안내한다. 이전에 등록된 코드의 상품이 나중에 삭제돼도 기존 주문을 조회할 수 있도록 그 코드는 유지할 수 있다.

등록 목록은 관리자 공통이다. InnoDB 설정 테이블과 목록 해시로 동시 저장 덮어쓰기를 막고, 잘못된 코드/저장 실패 시 기존 목록을 유지한다. 모두 비우고 저장하면 사입제품 지정이 해제된다.

ON에서는 등록 코드가 하나라도 들어 있는 주문 전체를 한 번 표시한다. 옵션·복수 사입상품이 있어도 주문 건수/합계가 중복되지 않는다. 상품 취소/반품 이력도 포함한 '주문 당시 포함 여부' 기준이며, 주문상태는 기존 필터로 좁힌다. 금액은 기존 주문 전체 기준이다.

상단 검색과 하단 상태/기간 검색, 정렬·페이지 이동·회원/수령인 바로검색, 목록 일괄처리 후 이동에서 조건을 유지한다. ON에서 상세 진입 후 목록으로 돌아갈 때도 조건을 보존한다. 상세 처리 POST를 변경하지 않기 위해 관리자 세션에 최근 10주문의 목록 복귀 주소만 보관한다. 같은 주문을 여러 탭에서 열면 마지막으로 진입한 조건이 상세 처리 후 복귀에 사용될 수 있다. 명시적 OFF 진입 시 해당 주문의 보관 조건을 지운다. 상단 기존 `전체목록`은 모든 조건을 초기화한다.

## 변경 범위

운영 파일 6개:

- `www/adm/shop_admin/orderlist.php` (코어): 버튼·ON/OFF, 두 검색 폼의 조건 보존, 필터 조회 및 건수, 오류 안내.
- `www/adm/shop_admin/orderform.php` (코어): 상세의 목록 복귀 주소만 보존. 결제·입금·재고·취소·포인트 처리 변경 없음.
- `www/adm/shop_admin/orderlistupdate.php` (코어): 일괄처리 완료 후 검증된 ON/필터 조건으로 돌아오는 주소만 추가.
- `www/adm/shop_admin/orderlistdelete.php` (코어): 위와 같은 목록 복귀 주소만 추가.
- `www/adm/shop_admin/redtoy_purchase.php`: 상품코드 관리.
- `www/lib/redtoy_purchase.lib.php`: 입력·설정 저장·인덱스 확인·조회·목록 상태 처리. 웹 요청에서 CREATE/ALTER를 하지 않는다.

테스트 2개, 적용·복구 SQL 예시 2개와 이 문서를 포함한다. 관리자 코어의 조회·검색 폼은 테마 스킨 훅이 없어 연결이 필요하다. 코어 업데이트 시 네 파일의 추가 부분을 확인한다. 별도 extend를 추가하지 않아 일반 쇼핑몰 요청 경로는 바뀌지 않는다.

## 성능 검토

- OFF: 사입제품 설정/인덱스 조회가 없다. 기존 목록의 SQL 세 개만 그대로 실행함을 DB 세션의 Questions 수로 검증했다.
- ON: 작은 설정 테이블 1행 확인과 SHOW INDEX 1회 후, 대상 주문번호를 중복 제거하여 먼저 추리고 주문번호 인덱스로 단건 접근한다. 주문·상품 테이블의 행을 PHP로 전부 가져오거나 상품별 반복 조회하지 않는다.
- 상품코드로 시작하고 주문번호까지 포함하는 전체 길이의 인덱스가 반드시 있어야 한다. 기존 적합한 인덱스를 재사용한다. 없으면 무거운 조회를 실행하지 않고 안내와 빈 목록을 표시한다. 비활성/무시 인덱스는 사용 가능하다고 보지 않는다.
- 등록 코드가 없거나 DB 초기화가 안 됐을 때 전체 주문으로 잘못 풀리지 않는다. ON은 빈 결과/오류 안내로 종료하고 OFF는 정상 동작한다.
- 운영 MariaDB에서 ON의 건수/목록 SELECT 각각에 1초 실행 제한을 적용한다. 제한 초과 시 범위를 줄이라는 안내를 내고 종료한다. DB 세션 전체 제한이나 다른 주문 쿼리는 바꾸지 않는다.
- 설정 저장은 작은 전용 테이블만 트랜잭션으로 변경하며 주문/상품 테이블을 잠그거나 수정하지 않는다.

로컬 MariaDB, 엔진별 합성 주문 100,000건/상품행 400,000건, 첫 준비 실행 후 3회 중앙값이다. 아래는 HTML 전체 응답 시간이 아니라 설정/인덱스 확인 + 건수/첫 20건 조회의 합계다. OS 캐시/동시 부하/실제 스키마에 따라 운영 결과는 달라진다.

| 조건 | InnoDB | MyISAM |
|---|---:|---:|
| OFF | 5.551ms | 0.073ms |
| 사입 포함 100주문 | 0.305ms | 0.621ms |
| 사입 포함 9,900주문 | 20.236ms | 45.178ms |
| 사입 포함 100,000주문 | 149.117ms | 431.643ms |
| 500코드·모든 100,000주문/400,000행 포함 | 401.502ms | 694.545ms |
| 100주문 중 배송 33주문 | 0.458ms | 0.769ms |
| 기간·배송으로 1,886주문 | 20.344ms | 62.059ms |

EXPLAIN은 소수 코드 조건의 상품행 접근이 ref / covering index / Using index임을 검증했다. 최대 500코드로 모든 상품행이 해당하는 조건에서는 covering index 전체 스캔(index / Using index)과 중복 제거 임시 테이블을 사용한다. 주문은 PRIMARY 또는 PK를 포함하는 InnoDB 상태 인덱스로 eq_ref 단건 접근한다. 대상이 거의 모든 주문이면 필터 OFF보다 비용이 커진다. '무조건 지연 0'을 보장할 수는 없다. 특히 새 인덱스를 추가하면 저장 공간과 상품행 INSERT/UPDATE의 인덱스 유지 비용이 생긴다. 실제 운영 인덱스·엔진·컬럼형/문자셋과 조회 계획은 아직 확인하지 않았으므로, 기존 인덱스 재사용 가능 여부를 먼저 확인해야 한다.

## DB 적용 절차

대상은 실제 dbconfig에 연결된 DB다. 아래 g5_는 예시이며 실제 테이블 매핑/접두사로 맞춘다. 운영 DB에 Codex가 직접 접속하거나 적용하지 않는다.

1. 코드 반영 전 실제 주문/상품/상품마스터 테이블 이름과 상품코드 컬럼의 charset/collation, cart 인덱스를 읽기 전용 확인한다. 코드는 동일한 it_id 비교 규칙을 전제로 하므로 새 테이블의 it_id를 cart와 맞춘다.
2. 기존 보안 백업 계정/옵션 파일로 cart 테이블을 백업하고 gzip 검사/체크섬을 남긴다. 새 설정 테이블이 이미 있으면 그것도 백업한다. 비밀번호를 명령줄이나 Git에 남기지 않는다. 예: `mariadb-dump --defaults-extra-file=/비공개경로/client.cnf --lock-tables --quick --hex-blob --no-tablespaces 실제DB g5_shop_cart | gzip > /비공개경로/cart-before.sql.gz` (셸에서 `set -o pipefail`, 디렉터리 0700/umask 077 사용). 읽기 잠금의 서비스 영향도 고려하여 실행한다.
3. `redtoy-purchase-migration.sql.example`에서 CREATE TABLE 부분을 먼저 적용한다. 기존 설정 테이블이 있다면 SHOW CREATE TABLE로 InnoDB/PK/컬럼 구조를 확인하고 불일치 시 중단한다. 기존 주문·상품 데이터는 변경하지 않는다.
4. cart에 `(it_id, od_id)` 또는 it_id로 시작하면서 od_id까지 포함하는 비활성화되지 않은 전체 길이 인덱스가 있으면 추가 DDL을 하지 않는다.
5. 적합한 인덱스가 없을 때만 예시의 주석 처리된 ADD INDEX를 검토한다. MyISAM의 인덱스 생성은 쓰기를 막을 수 있다. 운영 데이터량/여유 공간을 확인하고 별도 조용한 작업 시간에 진행한다. 인덱스 생성 중 무중단을 보장하지 않으며 웹 요청에서 자동 실행하지 않는다. InnoDB의 온라인 DDL도 실제 버전/정의에 맞게 검토한다.
6. 새 라이브러리/관리 화면을 먼저 놓고 코어 연결 파일을 적용한다. 수정 파일 원본 백업과 적용 충돌 검사, PHP 문법 검사 후 Apache를 재로드한다.
7. 소수의 실제 상품코드를 등록하고 아래와 같은 실제 EXPLAIN이 설정의 작은 범위, cart 커버링 인덱스, 주문번호 단건 인덱스를 이용하는지 확인한다. 코드의 1초 조회 제한과 별개로 운영 응답시간을 확인하고 문제가 있으면 OFF/코드 롤백한다.

```sql
SHOW FULL COLUMNS FROM g5_shop_cart WHERE Field IN ('it_id','od_id');
SHOW INDEX FROM g5_shop_cart;
SHOW TABLE STATUS LIKE 'g5_shop_cart';
SHOW CREATE TABLE g5_redtoy_purchase_item;
EXPLAIN SELECT COUNT(od_id)
FROM (
  SELECT DISTINCT pc.od_id AS redtoy_purchase_order_id
  FROM g5_redtoy_purchase_item pi
  INNER JOIN g5_shop_cart pc ON pc.it_id=pi.it_id
) redtoy_purchase_orders
STRAIGHT_JOIN g5_shop_order
  ON g5_shop_order.od_id=redtoy_purchase_orders.redtoy_purchase_order_id;
```

검증: 코드 목록 저장·재조회, ON/OFF, 혼합 주문/동일 상품 옵션의 중복 없음, 배송·완료·전체취소/부분취소, 검색+기간+결제수단, 정렬/페이지 이동, 관리자 권한·CSRF, 일괄 처리/상세에서 목록 복귀를 확인한다. 기능 자체는 외부 PG/알림 API를 호출하지 않는다. 실제 주문 상태 변경은 기존 업무이므로 단순 조회 검증 중 실행하지 않는다.

## 복구

ON을 끄면 기존 목록을 바로 사용한다. 코드 롤백은 운영 파일 6개의 이번 변경만 역패치/원본 복원하고 Apache를 재로드한다. 원래 업무 로직과 데이터를 되돌리는 작업은 아니다. 설정 테이블과 인덱스를 남겨도 이전 코드와 호환되므로 긴급 복구 시 DDL을 하지 않는다.

완전히 제거하려면 설정 테이블 덤프를 보존한 뒤 `redtoy-purchase-rollback.sql.example`의 주석 SQL을 별도 검토해 실행한다. 기존에 있던 인덱스를 지우지 않으며, 이 기능 때문에 실제 추가한 인덱스만 별도 작업 시간에 제거한다. 주문·회원·상품 DB 덤프 전체를 기능 롤백 이유로 복원하지 않는다.

## 로컬 검증 명령

```sh
REDTOY_COMPLETION_TEST_SOCKET=/private/tmp/redtoy-completion-db.XXXXXX/db.sock php -d short_open_tag=1 tests/redtoy_purchase.php
REDTOY_COMPLETION_TEST_SOCKET=/private/tmp/redtoy-completion-db.XXXXXX/db.sock php tests/redtoy_purchase_performance.php
```

격리된 로컬 DB만 허용하고 테스트 DB는 종료 시 삭제한다. 운영 DB/설정/외부 API는 사용하지 않는다. 기능 검증 89개와 기존 입금 126개·취소 180개·배송완료 123개·결제 56개·결제표시 24개가 통과했다. PHP 파일 8개의 문법 검사가 통과했다. 코어의 기존 CRLF 줄바꿈을 보존하며 공백 검사는 `git -c core.whitespace=cr-at-eol diff --check`로 수행한다.
