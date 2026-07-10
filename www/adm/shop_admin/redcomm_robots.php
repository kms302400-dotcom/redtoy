<?php
$sub_menu = '600400';
include_once('./_common.php');
include_once(G5_EDITOR_LIB);

auth_check($auth[$sub_menu], "r");


$g5['title'] = 'robots.txt 설정';
include_once (G5_ADMIN_PATH.'/admin.head.php');


$robots_notice = "";
$exist_robots = file_exists(G5_PATH. "/robots.txt");
if($exist_robots) {
	$robots_notice = "<a href=\"/robots.txt\" target=_blank>robots.txt 확인하기</a>";
} else {
	$robots_notice = " :  robots.txt 없음";
}

$sql = " select robots from g5_redcomm_seo ";
$redcomm_seo =  sql_fetch($sql);
?>

<form name="fconfig" action="./redcomm_robotsupdate.php" method="post">
<input type="hidden" name="token" value="">
<section id="anc_scf_delivery">
 
    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>robots.txt</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row">내용</th>
            <td>
            <?php echo help('설정된 로봇룰에 따른 사이트 내 웹 페이지 수집 가능 여부를 확인할 수 있습니다.' . $robots_notice) ?>


            <textarea name="robots" id="robots" rows="7"><?php echo $redcomm_seo['robots'] ?></textarea>
            </td>
        </tr>

        <tr>
            <th scope="row">모든 검색엔진 허용</th>
            <td>
User-agent: * </br>
Allow: /
            </td>
        </tr>
        <tr>
            <th scope="row">모든 검색엔진 허용 <br> 관리자 페이지 수집 제외</th>
            <td>
User-agent: *</br> 
Disallow: /adm</br>
Allow: /
            </td>
        </tr>

        <tr>
            <th scope="row">sitemap 지정</th>
            <td>
User-agent: *</br> 
Allow: /</br>
Sitemap: http://www.example.com/sitemap.xml
            </td>
        </tr>



        <tr>
            <th scope="row">다른 검색엔진의 로봇에 대하여 수집을 허용하지 않고 <br> 관리자네이버 검색로봇만 수집 허용</th>
            <td>
User-agent: *</br> 
Disallow: / </br>
User-agent: Yeti </br>
Allow: /
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<div class="btn_fixed_top">
    <input type="submit" value="저장" class="btn_submit btn" accesskey="s">
</div>

</form>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>
