# 주문번호 예약과 결제 시도별 연결

## 문제와 범위

기존 get_uniqodid()는 당일 저장된 주문 수+1을 사용해 결제 중인 고객들끼리 같은 번호를 받을 수 있었다. 주문서 진입마다 ss_order_id가 바뀌며 orderform.sub.php가 단일 payinfo 슬롯을 비웠고, 다날 준비는 폼에 없는 od_id를 읽는 경로도 있었다. 결제 응답은 현재 세션 슬롯을 읽어 다른 탭의 번호/금액과 연결될 위험이 있었다.

현재 배포 소스 d7a56ab 기준으로 수정했다. 운영 브랜치는 마지막 확인 기준 codex/rollback-pg-keep-selected이며, 소스 공급 브랜치 codex/admin-reviews-telegram과 다르다. 기존 운영 주문/회원/장바구니 데이터를 수정하는 마이그레이션은 없다. 과거 누락 주문을 자동 복원하지 않는다.

## 동작

- 주문하기 진입에만 예약한다. 장바구니 진입/상품 담기에는 예약을 추가하지 않는다. 새로고침이나 새 주문서는 새로운 번호/토큰을 받는다.
- 한국시간 날짜(yyMMdd)+100001~999999 순번으로 새 번호를 만든다. 예: 261027100001. 기존 9자리 번호와 분리된 12자리 숫자로, unsigned bigint/PG 주문번호/포인트 관계키의 기존 길이 안에 들어간다.
- 전용 InnoDB 테이블의 날짜 PK에 LAST_INSERT_ID(last_serial+1)를 사용한 원자적 UPDATE로 즉시 예약한다. 기존 주문번호가 이미 있으면 해당 번호를 소비하고 다음 번호를 시도한다. 주문 포기/실패/취소 시 번호를 재사용하지 않는다. 한도 소진/DB 실패 시 기존 생성기로 되돌아가지 않고 청구 전에 중단한다.
- 예약 쿼리는 autocommit 상태에서만 실행한다. 다른 트랜잭션을 임의로 COMMIT하지 않는다. 예약 테이블의 DML은 개별 쿼리에 2초 잠금 대기 제한을 적용한다. 주문 테이블에 LOCK TABLES/ALTER를 하지 않는다.
- PHP 세션에 결제 시도별 32바이트 난수 토큰과 주문번호/소유자/장바구니/직접구매 구분을 저장한다. 카드/휴대폰 요청값 스냅샷도 각 시도에 저장하며, 다른 주문서 진입이 이를 비우지 않는다. PC 팝업 이름도 시도별로 분리한다.
- 폼에 checkout_token, od_id, sw_direct를 명시한다. 서버는 토큰/세션 소유자/예약번호를 확인하고 PG 요청 ORDERID를 서버 예약값으로 설정한다. GET/POST 토큰이 다르면 거절한다.
- 카드 RETURNURL/CANCELURL과 휴대폰 TargetURL/BackURL에 토큰을 포함한다. 토큰 자체를 결제 증명으로 신뢰하지 않는다. 다날 카드 AUTH/인증 반환의 TID 또는 ORDERID를 예약과 대조하고, 휴대폰 NCONFIRM의 ORDERID/AMOUNT/TID를 확인한 후에만 BILL/NBILL을 호출한다.
- 결제창을 열기 직전에 현재 상품금액·상품/주문/배송 쿠폰·추가배송비·사용 포인트로 PG 요청금액을 동기화해 수량 변경 후의 오래된 good_mny도 바로잡는다. 서버의 할인 계산식은 바꾸지 않는다. 기존 서버 상품/재고/쿠폰/배송비/포인트 검증 이후 계산한 금액을 결제 시작 시 보관한 금액과 비교한다. 최종 승인 응답과 저장 직전에도 주문번호/TID/금액을 대조한다.
- 주문서의 기존 수량 변경은 결제 준비 전 허용한다. 상품·옵션 구성 변경은 새 주문서가 필요하다. 결제 준비 후에는 수량/가격을 포함한 장바구니 변경을 거절해 달라진 금액으로 청구하지 않는다.
- 같은 장바구니의 다른 시도가 결제 중이면 중복 준비/접수를 거절한다. PHP의 사이트 세션 잠금을 유지하고 pending→validating→billing→billed→saved→complete 상태를 기록한다. 완결된 동일 시도 재전송은 소유자/저장된 거래를 확인해 기존 주문 조회로 돌려보내며 청구/포인트/쿠폰/알림 본문을 재실행하지 않는다.
- BILL/NBILL 전에 billing 상태를 실제 PHP 세션 파일에 먼저 기록하고 세션 잠금을 다시 확보·재검증한다. 프로세스가 강제 종료돼도 이 표식을 근거로 재청구를 차단한다. 정상 종료 중 응답 유실/처리 중단은 review로 보존한다. 기록되지 않은 SQL/통신 장애까지 자동 복원하는 결제 복구 시스템은 아니다. 이 경우 PG와 주문 대조가 필요하다.
- 상태/서식 맵은 최대 40개를 유지한다. 활성·미확정 결제를 우선 보존하며 오래된 미제출/완결/실패/취소 서식부터 비운다. 조회 시 24시간 초과 시도를 정리하고, 미결제 서식의 결제 시작은 2시간 이내로 제한한다. 실제 세션 만료/파일 정리는 기존 PHP 정책도 적용된다. 만료됐으면 결제 결과를 임의로 다른 주문에 연결하지 않는다.

## 파일

웹 파일 10개:

1. www/lib/redtoy_checkout.lib.php: 예약, 시도별 세션, 소유자/장바구니/PG 응답 검증, 상태/재전송 처리.
2. www/shop/orderform.php (코어): 기존 번호 함수 호출을 예약으로 대체.
3. www/shop/orderform.sub.php (코어): 토큰/번호 입력 필드, 단일 payinfo 초기화 제거.
4. www/shop/danal/orderform.1.php: PC 결제 팝업을 시도별로 분리.
5. www/shop/danal_ready.php: 예약 검증, 서버 ORDERID, 카드 반환/취소 URL 토큰, 준비 응답 연결.
6. www/shop/danal_ready_teledit.php: 위 휴대폰 경로 연결.
7. www/shop/orderformupdate.php (코어): 가장 먼저 시도를 복원하고 승인·저장·완료 및 재전송을 검증.
8. www/mobile/shop/orderformupdate.php (코어): 구형 직접접수도 토큰 필수, 다날 콜백은 공통 shop 처리 경로만 허용. 현재 모바일 주문서는 PC와 같은 공통 주문서/다날 처리 경로를 사용한다.
9. www/shop/danal_cancel.php: 기존 기본 session_start 대신 사이트 공통 세션으로 취소 시도만 종료. 실제 결제 취소 API는 호출하지 않는다.
10. www/extend/redtoy_order_log.extend.php: 최신 전역 ss_order_id 대신 검증된 토큰의 주문번호로 기록.

테스트 3개: tests/redtoy_checkout_reservation.php 신규, tests/redtoy_checkout_points.php/ tests/redtoy_order_log.php의 연결 계약 갱신. DB 예시와 이 문서 포함 총 15개 파일.

고객/관리자 디자인, 회원 권한/성인인증 판정, 주문 금액·쿠폰·포인트 계산식, 기존 PG 인증값은 그대로 유지하되, 기존 하드코딩된 휴대폰 ID/비밀번호/상품코드는 배포 시 www/data/redtoy_danal_phone.php로 추출해 연결한다. 이 파일은 Git 제외 대상이며 _GNUBOARD_ 가드와 0640 권한으로 보호한다. 카드 PG 라이브러리의 기존 인증 설정은 수정하지 않는다. 변경 파일의 과거 요청/PG 응답 덤프 예시는 민감정보의 재커밋을 막기 위해 제거했다. 코어 4개를 연결하는 이유는 번호 발급/결제 승인·주문 저장 직전 훅이 없어 확정된 시점의 호출이 필요하기 때문이다. 향후 코어 업데이트 시 이 연결을 확인한다.

## DB와 전환 순서

신규 테이블 실제접두사_redtoy_order_sequence 하나: reservation_date DATE PK, last_serial INT UNSIGNED. 날짜별 마지막 예약 순번만 저장하며 개인정보/PG 키/세션 토큰을 DB에 추가 저장하지 않는다. 런타임 CREATE/ALTER는 없다. 운영 DB에 Codex가 직접 적용하지 않는다.

1. 운영 브랜치/HEAD/변경 파일/기존 컬럼 길이를 확인하고 실제 설정과 원격 소스가 다른 점을 보존한다. 변경하는 기존 파일은 비공개 폴더에 백업한다. 기존 예약 테이블이 있으면 먼저 비공개 덤프하고 초기화하지 않는다.
2. 코드를 반영하기 전에 기존 휴대폰 준비 파일의 ID/비밀번호/상품코드를 읽어 비공개 설정 파일을 생성·검증한다. 화면이나 명령줄에 값을 출력하지 않는다. 이미 설정 파일이 있다면 덮어쓰지 않고 일치 여부를 확인한다.
3. 새 주문서와 다날 결제 준비 3개 진입점만 Apache에서 일시 차단한다. 기존 결제 결과와 취소 콜백은 열어 둔다. 세션 조회 결과와 PG 진행 건을 대조하고 이미 열린 결제창/콜백이 끝난 뒤에만 전환한다. 세션 후보가 0이어도 결제 종료를 증명하지는 않는다. 후보를 없애려고 세션을 삭제하지 않는다. 토큰 없는 옛 주문서는 적용 후 새로 열어야 하며, 옛 결제창이 진행 중이면 코드 전환을 보류한다.
4. migration.sql.example의 테이블은 실제로 2번 준비 단계에서 먼저 추가하며, 3번 차단 전까지 웹 코드는 그대로 둔다. 기존 대형 테이블의 구조/데이터는 변경하지 않는다. SHOW CREATE TABLE로 InnoDB, 날짜 PK, serial 컬럼을 확인한다. DB 사용자는 SELECT/INSERT/UPDATE가 필요하며 LOCK TABLES 권한은 필요하지 않다.
5. 신규 라이브러리→로그 extend→준비/결과/취소 처리→주문서 PHP/폼/팝업 순서로 전체 코드를 배치한다. 부분 반영 상태로 고객 테스트를 진행하지 않는다. 새 예약 테이블이 없으면 주문서 진입이 안전하게 중단된다.
6. PHP 문법, 적용 역패치 가능 여부, Apache 설정을 확인하고 재로드한다. 새 주문번호의 실제 PG 전달과 반환 URL을 승인된 테스트/실제 업무에서 확인한다. 모의 검증만으로 운영 PG 성공을 보장하지 않는다.
7. 현재 사용 중인 무통장·다날 신용카드/휴대폰의 PC·모바일 공통 경로가 검증 범위다. 다른 PG/가상계좌/별도 간편결제 활성 여부는 운영 설정에서 확인해야 하며, 해당 공급자로 전환하면 별도 연결 검증이 필요하다.

테이블/순번 행은 예약 시작 후 DELETE/TRUNCATE/DROP하거나 과거 값으로 복원하지 않는다. 번호 간격은 정상이며 과거 주문 수로 다시 맞추지 않는다. 특히 파일 정리와 DB 예약 카운터 초기화를 혼동하지 않는다.

## 복구

미반영 단계라면 변경 파일만 되돌리고 비어 있는 새 테이블을 남겨도 된다. 실제 반영 후 문제가 생기면 먼저 새 주문서/결제 준비를 잠시 중단하고, 진행 중인 요청의 PG 상태와 신규 주문을 대조한다. 백업으로 웹 파일 10개의 이번 변경만 되돌리고 Apache를 재로드한다. DB 예약 테이블과 기존/신규 주문 데이터는 보존하며 덤프 전체를 덮어쓰지 않는다.

전체 코드 롤백은 기존 ‘주문 수+1’ 발급 방식도 되돌리므로 중복 위험이 다시 생긴다. 결제 진행을 재개하기 전에 원인에 따라 예약 부분을 유지하는 보정 또는 재배포를 검토한다. 활성 결제 시도와 여러 탭의 데이터가 있는 상태에서 라이브러리만 제거하거나 준비/결과 코드만 서로 다른 버전으로 혼용하지 않는다. 기존 누락 거래의 주문 복구는 이 배포와 별개로 PG·고객·상품 정보를 대조한 후 처리한다.

## 검증

네트워크 차단 소켓 전용 로컬 MariaDB와 합성 데이터만 사용했다. 실제 PG/본인인증/알림 API는 호출하지 않았다. 카드/휴대폰 준비 PHP 및 승인 코드 조각은 외부 전송을 모의 함수로 대체하고 기존 인증정보 리터럴도 합성값으로 교체해 실행했다. 실제 운영 환경/브라우저와 실제 PG 응답은 미검증이다.

- MyISAM/InnoDB 각 133개(번들 Node로 실행한 실제 결제 JS 검증 9개 포함): 12개 프로세스의 300회 동시 번호 예약, 기존 ID 회피, 트랜잭션·잠금 제한, 포기 번호 미재사용, 새 서식 분리, 회원/비회원·소유자·토큰 검증, 두 탭의 카드/휴대폰 복원, 준비 URL의 동일 토큰/ORDERID, 수량 변경 시점, 금액/TID/주문번호 불일치 시 청구 전 차단, 승인 중단/유실 차단, 취소 범위, 기존 주문 불변.
- 실제 PHP 파일 세션을 공유하는 8개 동시 콜백: 모의 청구 1회·주문 1건, 나머지 7개는 기존 주문 조회 또는 처리 중 차단. 모의 청구 직후 자식 프로세스를 강제 종료하고 재요청해도 청구가 한 번임을 별도로 확인했다. 엔진마다 검증했다.
- 기존 입금 126·PG 취소 180·배송완료 123·결제 포인트 56·금액 표시 24 = 509개 통과.
- 주문 로그 46개 통과. 로그는 토큰의 예약번호로 분리하며 실제 주문을 수정하지 않는다.
- 변경 PHP 전부 short_open_tag=1 문법 검사, git -c core.whitespace=cr-at-eol diff --check. 기존 CRLF 파일의 줄바꿈 유지.

결제 JS 추가 검증은 REDTOY_CHECKOUT_TEST_NODE에 기존 Node 실행 경로를 지정한다. 운영 애플리케이션의 신규 의존성은 아니다.

실행 예: REDTOY_COMPLETION_TEST_SOCKET=/private/tmp/redtoy-completion-db.XXXXXXXX/db.sock php -d short_open_tag=1 tests/redtoy_checkout_reservation.php myisam (또는 innodb).


## 운영자 실행 도우미

아래 코드는 운영자가 SSH에서 명시적으로 실행하는 배포 도우미다. 애플리케이션 런타임에서는 실행하지 않는다. 별도 실행 파일을 저장소에 추가하지 않고, 검토한 이 문서의 지정 블록을 준비 폴더로 추출한다. source는 이 변경 커밋의 체크아웃이어야 하며 위 기준 커밋을 조상으로 포함해야 한다. prepare → pause → check → 기존 결제 종료 확인 → apply 순서다.

- prepare: 기존 웹 파일 9개와 패치·해시·운영 Git 상태를 0700 준비 폴더에 보존한다. 휴대폰 설정을 기존 서버 파일에서 비공개 파일로 추출하고 신규 예약 테이블을 만든다. 이미 예약 테이블이 있으면 초기화하지 않고 중단한다. 새 테이블이 없었던 상태를 전제로 하므로 기존 DB 덤프를 복원할 변경은 없으며, 기존 주문·장바구니·회원 테이블에는 쓰지 않는다. 준비 도중 실패하면 폴더와 출력으로 마지막 단계를 확인하며 DDL을 무작정 반복하지 않는다.
- pause: 레드토이 shop 디렉터리의 orderform.php, danal_ready.php, danal_ready_teledit.php만 HTTP 403으로 임시 차단한다. 상품 조회·장바구니·관리자·기존 결제 콜백은 유지한다. 서버의 다른 쇼핑몰 디렉터리에는 적용하지 않는다.
- check: 세션 파일을 공유 잠금으로 읽어 기존 결제 후보·요청 중 잠금·읽기 실패 개수만 표시한다. 개인 데이터/세션 토큰을 출력하거나 세션을 삭제하지 않는다. 후보가 남아 원인을 확인하지 못했거나 PG 결제가 진행 중이면 적용하지 말고 확인한다. 세션 경로가 다르거나 읽기 권한이 없으면 확인을 완료하기 전 진행하지 않는다.
- apply: 차단 HTTP 403·원본 해시·패치·PHP 문법을 확인한다. OLD-PAYMENTS-FINISHED를 운영자가 입력해야 적용된다. 공용 라이브러리를 먼저 생성·권한 확인한 뒤 나머지 패치를 적용한다. 코드 반영 후 문법·역패치·쇼핑몰 HTTP 200·Apache 검사/재로드를 통과한 뒤 차단을 해제한다. 검사 실패 시 자동으로 결제를 재개하지 않는다. DB 기존 데이터나 Git 브랜치를 변경하지 않는다. 이전에 열어 둔 주문서는 새로 열어야 한다.
- rollback: 먼저 새 진입을 차단하고 신규 결제 종료/PG 대조 후 NEW-PAYMENTS-FINISHED를 입력해야 참조 코드를 먼저 역적용하고 마지막에 신규 라이브러리를 제거한다. 원본 해시를 확인한다. 예약 테이블·순번·비공개 설정·모든 주문은 남긴다. 구 코드의 중복번호 위험이 돌아오기 때문에 새 주문서 진입은 차단한 채로 둔다. 기존 주문 조회/관리와 콜백은 열려 있다. 복구 후 원인 검토 없이 차단을 해제하지 않는다.

결제 중 무중단 호환을 보장하는 배포가 아니다. 새 결제 진입의 짧은 중단을 사용해 진행 중 결제를 먼저 마친 뒤 전환한다. 강제 시간 제한으로 기존 결제창을 버리지 않는다. 전환 후 승인된 PC·모바일 무통장/카드/휴대폰 테스트에서 주문번호·금액·저장 주문·PG를 대조한다.

도우미 자체도 임시 합성 파일에서 인증정보 추출/비공개 연결, 후보 조회, 코드 반영/역적용, 진행 중 요청 시 중단을 검증했다. 실제 Apache/운영 DB/운영 PG 적용은 실행하지 않았다.

<!-- REDTOY_CHECKOUT_DEPLOY_BEGIN -->
```python
#!/usr/bin/env python3
"""Operator-run deployment helper. Never calls a payment API."""
import datetime
import fcntl
import hashlib
import json
import os
from pathlib import Path
import re
import subprocess
import sys
import tarfile

os.umask(0o077)
PROD = Path('/home/user/redtoy')
STAGE = Path(__file__).resolve().parent
SOURCE = STAGE / 'source'
META = STAGE / 'deployment.json'
CONF = Path('/etc/apache2/conf-available/redtoy-checkout-switch.conf')
BASE = 'd7a56ab8496e2e0c79e3a6b28b66a5afc7361381'
WEB = ['www/lib/redtoy_checkout.lib.php', 'www/shop/orderform.php',
       'www/shop/orderform.sub.php', 'www/shop/danal/orderform.1.php',
       'www/shop/danal_ready.php', 'www/shop/danal_ready_teledit.php',
       'www/shop/orderformupdate.php', 'www/mobile/shop/orderformupdate.php',
       'www/shop/danal_cancel.php', 'www/extend/redtoy_order_log.extend.php']
PHP = '/usr/bin/php'

def run(args, **kw):
    return subprocess.run(args, check=True, **kw)

def output(args):
    return subprocess.check_output(args, text=True).strip()

class DeploymentError(Exception):
    pass

def require(ok, message):
    if not ok:
        raise DeploymentError(message)

def save(data):
    tmp = STAGE / 'deployment.next'
    tmp.write_text(json.dumps(data, ensure_ascii=True))
    os.replace(tmp, META)

def php(body):
    # Suppress DB/credential exceptions; do not print their message or trace.
    code = '''<?php
define('_GNUBOARD_',true);
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
try {
require '/home/user/redtoy/www/data/dbconfig.php';
$db=new mysqli(G5_MYSQL_HOST,G5_MYSQL_USER,G5_MYSQL_PASSWORD,G5_MYSQL_DB);
$db->set_charset('utf8mb4');
''' + body + '''
} catch(Throwable $e) {
fwrite(STDERR,"DB/settings verification failed; details suppressed.\\n"); exit(1);
}
'''
    run([PHP, '-d', 'short_open_tag=1', '-d', 'display_errors=0',
         '-d', 'log_errors=0'], input=code, text=True)

def verify_source(meta):
    require(output(['git','-C',str(SOURCE),'rev-parse','HEAD']) == meta['commit'],
            'Source revision changed')
    run(['git','-C',str(SOURCE),'diff','--exit-code','HEAD','--'] + WEB,
        stdout=subprocess.DEVNULL)
    patch = STAGE / 'deploy.patch'
    require(hashlib.sha256(patch.read_bytes()).hexdigest() == meta['patch_sha256'],
            'Patch checksum changed')
    for file in WEB:
        run([PHP,'-d','short_open_tag=1','-l',str(SOURCE/file)], stdout=subprocess.DEVNULL)

def apache():
    run(['sudo','apache2ctl','-t'])
    run(['sudo','systemctl','reload','apache2'])

def private_phone():
    def literal(file, name):
        text = file.read_bytes().decode('latin1')
        matches = re.findall(r'^\s*'+re.escape(name)+r'''\s*=\s*(["'])([^"'\r\n]+)\1\s*;''', text, re.M)
        values = {m[1] for m in matches}
        require(len(values)==1, 'Cannot locate an unambiguous existing phone setting')
        return values.pop()
    ready = PROD/'www/shop/danal_ready_teledit.php'
    values = {'id':literal(ready,'$TransR["ID"]'),
              'password':literal(ready,'$TransR["PWD"]'),
              'item_code':literal(ready,'$ItemCode')}
    require(re.fullmatch(r'[A-Za-z0-9]{10}',values['id']) is not None, 'Invalid phone ID format')
    require(re.fullmatch(r'[A-Za-z0-9]{1,128}',values['password']) is not None, 'Invalid password format')
    require(re.fullmatch(r'[A-Za-z0-9]{1,32}',values['item_code']) is not None, 'Invalid item code format')
    require(literal(PROD/'www/shop/orderformupdate.php','$TransR["CPID"]') == values['id'],
            'Phone approval ID differs from preparation ID')
    target = PROD/'www/data/redtoy_danal_phone.php'
    content = "<?php\nif (!defined('_GNUBOARD_')) exit;\nreturn array(\n"
    content += ''.join("    '%s' => '%s',\n" % (k,v) for k,v in values.items()) + ");\n"
    # Values validated to alphanumerics before serialization; never stdout/argv.
    if target.exists():
        require(not target.is_symlink() and target.read_text()==content,
                'Private settings already exist and differ; stop without overwrite')
    else:
        temp = STAGE/'phone.private.php'
        temp.write_text(content)
        try:
            run(['sudo','install','-o','user','-g','www-data','-m','0640',str(temp),str(target)])
        finally:
            temp.unlink(missing_ok=True)
    run(['sudo','-u','www-data',PHP,'-d','display_errors=0','-d','log_errors=0','-r',
         "define('_GNUBOARD_',true); $v=require '/home/user/redtoy/www/data/redtoy_danal_phone.php'; exit(is_array($v)&&isset($v['id'],$v['password'],$v['item_code'])?0:1);"])
    print('Private phone settings ready; no values printed.')

def prepare():
    require(not META.exists(), 'Already prepared; use check, not prepare again')
    run(['git','-C',str(SOURCE),'merge-base','--is-ancestor',BASE,'HEAD'])
    commit=output(['git','-C',str(SOURCE),'rev-parse','HEAD'])
    files=output(['git','-C',str(SOURCE),'diff','--name-only',BASE,'HEAD','--','www']).splitlines()
    require(set(files)==set(WEB), 'Unexpected web patch scope')
    patch=subprocess.check_output(['git','-C',str(SOURCE),'diff','--binary',BASE,'HEAD','--','www'])
    (STAGE/'deploy.patch').write_bytes(patch)
    meta={'commit':commit, 'patch_sha256':hashlib.sha256(patch).hexdigest(), 'applied':False}
    verify_source(meta)
    run(['git','-C',str(PROD),'apply','--check',str(STAGE/'deploy.patch')])
    require(not (PROD/WEB[0]).exists(), 'Checkout library already exists; investigate before redeployment')
    (STAGE/'production-before.txt').write_text(output(['git','-C',str(PROD),'status','--short','--branch'])+'\n'+output(['git','-C',str(PROD),'rev-parse','HEAD'])+'\n')
    with tarfile.open(STAGE/'code-before.tar.gz','w:gz') as archive:
        for name in WEB[1:]:
            archive.add(PROD/name, arcname=name, recursive=False)
    with tarfile.open(STAGE/'code-before.tar.gz','r:gz') as archive:
        require(set(archive.getnames())==set(WEB[1:]), 'Backup scope mismatch')
        meta['before']={m.name:hashlib.sha256(archive.extractfile(m).read()).hexdigest() for m in archive.getmembers()}
    meta['backup_sha256']=hashlib.sha256((STAGE/'code-before.tar.gz').read_bytes()).hexdigest()
    save(meta)  # Recovery metadata exists even if configuration/DDL needs attention.
    private_phone()
    php('''
$t=G5_TABLE_PREFIX.'redtoy_order_sequence';
if (!preg_match('/^[A-Za-z0-9_]+$/D',$t)) throw new RuntimeException();
$stmt=$db->prepare('SELECT COUNT(*) n FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');
$stmt->bind_param('s',$t); $stmt->execute();
if ((int)$stmt->get_result()->fetch_assoc()['n'] !== 0) throw new RuntimeException();
foreach (['g5_shop_order_table','g5_shop_cart_table'] as $key) {
 $name=$g5[$key]??''; if(!preg_match('/^[A-Za-z0-9_]+$/D',$name)) throw new RuntimeException();
 $col=$db->query("SHOW COLUMNS FROM `$name` WHERE Field='od_id'")->fetch_assoc();
 if(!$col || !preg_match('/bigint/i',$col['Type'])) throw new RuntimeException();
}
$db->query("CREATE TABLE `$t` (reservation_date DATE NOT NULL,last_serial INT UNSIGNED NOT NULL DEFAULT 100000,PRIMARY KEY(reservation_date)) ENGINE=InnoDB DEFAULT CHARSET=ascii");
$status=$db->query("SHOW TABLE STATUS WHERE Name='$t'")->fetch_assoc();
if(strcasecmp($status['Engine'],'InnoDB')!==0) throw new RuntimeException();
if((int)$db->query("SELECT COUNT(*) n FROM `$t`")->fetch_assoc()['n']!==0) throw new RuntimeException();
echo "Reservation table created and verified (empty). Existing business data unchanged.\\n";
''')
    meta['prepared']=True
    save(meta)
    print('PREPARED. Code backup: '+str(STAGE/'code-before.tar.gz'))

def pause():
    require(not CONF.exists() and not CONF.is_symlink(), 'Switch configuration already exists; do not overwrite')
    text='''# Redtoy checkout transition only
<Directory "/home/user/redtoy/www/shop">
    <FilesMatch "^(orderform|danal_ready|danal_ready_teledit)\\.php$">
        Require all denied
    </FilesMatch>
</Directory>
'''
    temp=STAGE/'switch.conf'
    temp.write_text(text)
    run(['sudo','install','-o','root','-g','root','-m','0644',str(temp),str(CONF)])
    run(['sudo','a2enconf','redtoy-checkout-switch'])
    apache()
    print('New checkout/preparation paused. Existing result callbacks stay open.')

def check():
    # Aggregate metadata only. Never decode/unserialize or change customer sessions.
    folder=PROD/'www/data/session'
    require(folder.is_dir(),'Confirm actual PHP session directory before cutover')
    candidates=busy=errors=0
    pattern=re.compile(rb'(?:payinfo\||s:7:"payinfo";)a:[1-9][0-9]*:\{')
    for file in folder.glob('sess_*'):
        try:
            with file.open('rb') as stream:
                try:
                    fcntl.flock(stream,fcntl.LOCK_SH|fcntl.LOCK_NB)
                except BlockingIOError:
                    busy+=1
                    continue
                raw=stream.read(4*1024*1024+1)
                if len(raw)>4*1024*1024:
                    errors+=1
                elif pattern.search(raw):
                    candidates+=1
        except OSError:
            errors+=1
    print('Legacy payment session candidates: %d / busy sessions: %d / unreadable: %d' % (candidates,busy,errors))
    print('Candidates may include stale sessions. Do not delete them to clear this check.')
    print('A zero count cannot prove every old PG window has closed; operator confirmation is required.')
    return candidates,busy,errors

def switched():
    require(CONF.exists() and (Path('/etc/apache2/conf-enabled')/CONF.name).exists(), 'Pause new checkout first')
    require(CONF.read_text()==(STAGE/'switch.conf').read_text(),'Unexpected switch configuration')
    url='https://www.redtoy.co.kr/shop/orderform.php'
    status=output(['curl','--silent','--show-error','--max-time','20','--output','/dev/null','--write-out','%{http_code}',url])
    require(status=='403','New checkout is not blocked externally; stop before replacing code')

def release():
    run(['sudo','a2disconf','redtoy-checkout-switch'])
    apache()
    # Keep disabled conf file for provenance; never remove another configuration.
    print('New checkout reopened.')

def apply(meta):
    require(meta.get('prepared') and not meta['applied'],'Not prepared or already applied')
    switched()
    candidates,busy,errors=check()
    require(busy==0 and errors==0,'Session requests still running or unreadable; investigate before cutover')
    print('Confirm all old payment windows/callbacks have ended, including candidates reviewed against PG.')
    require(input('Type OLD-PAYMENTS-FINISHED: ').strip()=='OLD-PAYMENTS-FINISHED','Cutover cancelled')
    verify_source(meta)
    for name,digest in meta['before'].items():
        require(hashlib.sha256((PROD/name).read_bytes()).hexdigest()==digest,'Production file changed since backup')
    run(['git','-C',str(PROD),'apply','--check',str(STAGE/'deploy.patch')])
    # Common extend can be loaded by every page: its dependency must exist first.
    library=PROD/WEB[0]
    with library.open('xb') as stream:
        stream.write((SOURCE/WEB[0]).read_bytes())
    library.chmod(0o644)
    previous=os.umask(0o022)
    try:
        run(['git','-C',str(PROD),'apply','--exclude='+WEB[0],'--whitespace=nowarn',str(STAGE/'deploy.patch')])
    finally:
        os.umask(previous)
    meta['applied']=True
    save(meta)
    for file in WEB:
        run([PHP,'-d','short_open_tag=1','-l',str(PROD/file)])
    run(['git','-C',str(PROD),'apply','--reverse','--check',str(STAGE/'deploy.patch')])
    apache()
    status=output(['curl','--silent','--show-error','--max-time','20','--output','/dev/null','--write-out','%{http_code}','https://www.redtoy.co.kr/shop/'])
    require(status=='200','Shop smoke check failed; checkout remains paused')
    release()
    print('APPLIED. No existing order/payment records changed. Confirm PC/mobile new checkout in browser.')

def rollback(meta):
    require(meta['applied'],'No recorded application to reverse')
    if not (Path('/etc/apache2/conf-enabled')/CONF.name).exists():
        # Reuse only the switch file produced by this stage.
        require(CONF.read_text()==(STAGE/'switch.conf').read_text(),'Unexpected switch configuration')
        run(['sudo','a2enconf','redtoy-checkout-switch']); apache()
    switched()
    print('Finish/reconcile NEW in-flight payments before restoring old handlers.')
    require(input('Type NEW-PAYMENTS-FINISHED: ').strip()=='NEW-PAYMENTS-FINISHED','Rollback cancelled')
    require(hashlib.sha256((STAGE/'code-before.tar.gz').read_bytes()).hexdigest()==meta['backup_sha256'],'Backup checksum mismatch')
    run(['git','-C',str(PROD),'apply','--reverse','--check',str(STAGE/'deploy.patch')])
    # Remove the shared dependency only AFTER all references are reverted.
    run(['git','-C',str(PROD),'apply','--reverse','--exclude='+WEB[0],str(STAGE/'deploy.patch')])
    (PROD/WEB[0]).unlink()
    for name,digest in meta['before'].items():
        require(hashlib.sha256((PROD/name).read_bytes()).hexdigest()==digest,'Restored file mismatch')
        run([PHP,'-d','short_open_tag=1','-l',str(PROD/name)], stdout=subprocess.DEVNULL)
    meta['applied']=False; meta['rolled_back']=True; save(meta)
    apache()
    print('ROLLED BACK. Checkout remains paused: old duplicate-number bug would return.')
    print('Keep the reservation table, its rows, private settings and ALL orders. Do not restore an old DB dump.')

try:
    require(output(['id','-un'])=='user','Run as SSH user, not root')
    require(str(STAGE).startswith('/home/user/redtoy_checkout_reservation_'),'Unexpected staging path')
    require(STAGE.stat().st_uid==os.getuid() and STAGE.stat().st_mode & 0o077 == 0,'Staging must be owned by user and private (0700)')
    require(len(sys.argv)==2 and sys.argv[1] in ('prepare','pause','check','apply','rollback'),'Use prepare|pause|check|apply|rollback')
    action=sys.argv[1]
    if action=='prepare':
        prepare()
    else:
        require(META.is_file(),'Prepare first')
        meta=json.loads(META.read_text())
        require(meta.get('prepared'), 'Preparation incomplete; investigate without rerunning DDL')
        if action=='pause': pause()
        elif action=='check': check()
        elif action=='apply': apply(meta)
        elif action=='rollback': rollback(meta)
except DeploymentError as error:
    print('STOPPED: '+str(error),file=sys.stderr)
    sys.exit(1)
except (Exception,KeyboardInterrupt):
    print('STOPPED. Do not repeat payment/cancel requests. Check the last completed step; credentials suppressed.',file=sys.stderr)
    sys.exit(1)
```
<!-- REDTOY_CHECKOUT_DEPLOY_END -->
