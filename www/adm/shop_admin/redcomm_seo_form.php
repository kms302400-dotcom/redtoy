<?php
$sub_menu = '600300';
include_once('./_common.php');
include_once(G5_EDITOR_LIB);

auth_check($auth[$sub_menu], "r");

$sql= " SELECT 1 FROM information_schema.tables WHERE TABLE_NAME like 'g5_redcomm_seo'" ;
$row = sql_fetch($sql);

if(empty($row[1])){
    alert('SEO 관련 테이블이 없습니다. 다시 설치 부탁드립니다.', G5_ADMIN_URL);
}


$g5['title'] = 'SEO 기본 정보';
include_once (G5_ADMIN_PATH.'/admin.head.php');

$sql = " select * from g5_redcomm_seo ";
$redcomm_seo =  sql_fetch($sql);

?>

<form name="fconfig" action="./redcomm_seo_formupdate.php"  method="post" enctype="MULTIPART/FORM-DATA">
    <input type="hidden" name="token" value="">

    <section id="anc_scf_delivery">

        <div class="tbl_frm01 tbl_wrap">
            <table>
                <caption>기본 정보 입력</caption>
                <colgroup>
                    <col class="grid_4">
                    <col>
                </colgroup>
                <tbody>
                <tr>
                    <th scope="row"><label>대표 URL</label></th>
                    <td>
                        <input type="url" name="canonical" value="<?php echo $redcomm_seo['canonical']; ?>" size="80" class="frm_input" id="canonical">
                        <span>※ 도메인을 입력해주세요.</span>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label>타이틀</label></th>
                    <td>
                        <input type="text" name="seo_title" value="<?php echo $redcomm_seo['seo_title']; ?>" size="80"
                               class="frm_input" id="seo_title">
                        <span>※ 쇼핑몰의 이름을 입력해주세요.</span>
                    </td>
                </tr>

                <tr>
                    <th scope="row">설명</th>
                    <td>
                        <textarea name="seo_description" id="seo_description" rows="4" size="30"><?php echo $redcomm_seo['seo_description'] ?></textarea>
                        <br>
                        <br>
                        <span>※ 쇼핑몰에 대한 설명을 입력해주세요.</span>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label>구글 인증 코드</label></th>
                    <td>
                        <input type="text" name="google" value="<?php echo $redcomm_seo['google']; ?>" size="120"
                               class="frm_input" id="google">
                        <span>※ 구글 서치콘솔의 인증코드를 넣어주세요.</span>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label>네이버 인증 코드</label></th>
                    <td>
                        <input type="text" name="naver" value="<?php echo $redcomm_seo['naver']; ?>" size="120"
                               class="frm_input" id="naver">
                        <span>※ 네이버 웹마스터도구의 인증코드를 넣어주세요.</span>
                    </td>

                </tr>


                <tr>
                    <th scope="row">페이스북 대표 이미지</th>
                    <td>
                        <?php echo help("페이스북 공유 시 보여지는 대표이미지의 크기는 가로 1200픽셀, 세로 628픽셀입니다. 파일형식은 jpg, png로 등록해주세요."); ?>

                        <input type="file" name="facebook_img" id="facebook_img" accept=".jpg, .png">
                        <?php
                        $it_img = G5_DATA_PATH.'/common/'.$redcomm_seo['facebook_img'];
                        // $it_img_exists = run_replace('shop_item_image_exists', (is_file($it_img) && file_exists($it_img)), $it, $i);
                        // if($it_img_exists) {
                        if(is_file($it_img)) {
                            ?>
                            <label for="facebook_img_del"><span class="sound_only">페이스북 대표 이미지</span>파일삭제</label>
                            <input type="checkbox" name="facebook_img_del" id="facebook_img_del" value="1">
                            <a href="<?=G5_DATA_URL?>/common/<?=$redcomm_seo['facebook_img']?>"  target=_blank> 등록된 이미지 확인</a>
                        <?php } ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row">트위터 대표 이미지</th>
                    <td>
                        <?php echo help("트위터 공유 시 보여지는 대표이미지의 크기는 가로 1024픽셀, 세로 512픽셀입니다. 파일형식은 jpg, png로 등록해주세요."); ?>

                        <input type="file" name="twitter_img" id="twitter_img" accept=".jpg, .png">
                        <?php
                        $it_img = G5_DATA_PATH.'/common/'.$redcomm_seo['twitter_img'];
                        // $it_img_exists = run_replace('shop_item_image_exists', (is_file($it_img) && file_exists($it_img)), $it, $i);
                        // if($it_img_exists) {
                        if(is_file($it_img)) {
                            ?>
                            <label for="twitter_img_del"><span class="sound_only">트위터 대표 이미지</span>파일삭제</label>
                            <input type="checkbox" name="twitter_img_del" id="twitter_img_del" value="1">
                            <a href="<?=G5_DATA_URL?>/common/<?=$redcomm_seo['twitter_img']?>"  target=_blank> 등록된 이미지 확인</a>
                        <?php } ?>

                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </section>

    <div class="btn_fixed_top" style="margin: 0 0 10px;padding: 0 20px;">
        <input type="submit" value="확인" class="btn_submit btn" accesskey="s" style="margin: 0 0 10px;padding: 5px 10px;">
    </div>

</form>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>
