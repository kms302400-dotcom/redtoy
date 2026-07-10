<?php

	/********************************************************************************	
	  결제창 종료시에 PG사에서 호출하는 페이지 입니다.
	  상점에서 필요한 로직 추가	
	********************************************************************************/
?>
<meta name="viewport" content="width=device-width, user-scalable=no">
<script src="https://api-std.mainpay.co.kr/js/mainpay.pc-1.0.js"></script>
<script>
    alert('결제를 취소하셨습니다.');
    location.href='/shop/';
    self.close();
</script>