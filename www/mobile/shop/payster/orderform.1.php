<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
// 전자결제를 사용할 때만 실행

?>

<script src="https://ajax.aspnetcdn.com/ajax/jQuery/jquery-3.3.1.min.js"></script>
<script src="https://api.payster.co.kr/js/pgAsistant.js"></script>
</head>
<body>
<script type="text/javascript">

    function doPaySubmit(){
        // 결제창 호출 함수
        SendPay(document.payInit);
    }
    // 결제창 return 함수(pay_result_submit 이름 변경 불가능)
    function pay_result_submit(){
        payResultSubmit();
    }
    // 결제창 종료 함수(pay_result_close 이름 변경 불가능)
    function pay_result_close(){
        //alert('결제를 취소하였습니다.');
    }
</script>
