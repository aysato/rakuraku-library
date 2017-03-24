<?php 
/*
Plugin Name: rakuraku-liblary
Description: 画像や文書ファイルをフォルダ整理するプラグインです。
Version: 2.0
Author: 佐藤
License: GPL 
*/
///////////////////////////////////////////////////////////////////////////////////////////////////////////
//ライブラリ用カスタム投稿タイプの追加
add_action( 'init', 'create_post_type' );
function create_post_type() {
	register_post_type( 'library', /* ポストタイプ名 */
		array(
			'labels' => array(
			'name' => __( 'ライブラリー' ),
			'singular_name' => __( 'ライブラリー' )
			),
		'public' => true,
		'menu_position' =>5,
		'supports' => array('title')/* タイトルのみ指定していることによって、投稿画面にタイトルとカスタムフィールドのみ表示*/
    	)
	);
////////////////////////////////////////////////////////////////////////////////////////////////////////////
//カスタム投稿タイプのカテゴリ機能有効化
  register_taxonomy(
    'librarycat', /* タクソノミーの名前 */
    'library', /* library投稿で設定する */
    array(
      'hierarchical' => true, /* 親子関係が必要なければ false */
      'update_count_callback' => '_update_post_term_count',
      'label' => 'ライブラリのカテゴリー',
      'singular_label' => 'ライブラリのカテゴリー',
      'public' => true,
      'show_ui' => true
    )
  );
}
//////////////////////////////////////////////////////////////////////////////////////////////////////////////
//カスタム投稿タイプの公開時に自動で投稿IDをスラッグにセットする
function add_slug_for_posts($post_id) {
global $wpdb;
$posts_data = get_post($post_id, ARRAY_A);
$slug = $posts_data['post_name'];

	if ($post_id != $slug){
	  $my_post = array();
	  $my_post['ID'] = $post_id;
	  $my_post['post_name'] = $post_id;
	wp_update_post($my_post);
	}  
}
add_action('publish_library', 'add_slug_for_posts');
////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//管理画面の記事一覧に記事が所属しているカスタムタクソノミー名を表示する 
//記事一覧にカラムを追加しタイトルを指定
function manage_posts_columns($columns) {
$columns['librarycat'] = "カテゴリー";
return $columns;
}
function add_column($column){
//カテゴリー名取得、表示
if( 'librarycat' == $column ) {
echo get_the_term_list($post_id, 'librarycat');
}
}
add_filter('manage_edit-library_columns', 'manage_posts_columns');
add_action('manage_posts_custom_column', 'add_column');
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//管理画面でのカスタム投稿（ライブラリ）ソート
// カスタムタクソノミーでフィルター （絞り込み機能）===============================================================
add_action( 'restrict_manage_posts', 'add_post_taxonomy_restrict_filter' );
function add_post_taxonomy_restrict_filter() {
global $post_type;
if ('library' == $post_type) {
?>
<select name="librarycat">
  <option value="">カテゴリー指定なし</option>
  <?php
$terms = get_terms('librarycat', 'orderby=term_group');
//親タクソノミーの名称を取得して配列を変更=========================================================================
foreach ($terms as $term) {
if ($term->parent > 0) {
$parents = get_term($term->parent, 'librarycat');
$parent = $parents->name . " ";
$term->name = $parent . " " . $term->name;
} else {
$term->name = $term->name;
}
}
//親タクソノミーの名称を含んだ名前でソート=========================================================================
function compareArray($a, $b) {
if ( $a->name < $b->name ) return -1;
if ( $a->name > $b->name ) return 1;
return 0;
}
uasort($terms, "compareArray");
foreach ($terms as $term) {
//タクソノミーを選んだときにselectedがつくようにする===============================================================
if ($term->slug === $_REQUEST['librarycat']) {
$selected = " selected";
} else {
$selected = "";
}
//親タクソノミーが変わったら区切り線をいれる=======================================================================
if ($term->parent == 0) {
?>
  <option>--------------------</option>
  <?php
}
?>
  <option value="<?php echo $term->slug; ?>"<?php echo $selected; ?>><?php echo $term->name; ?></option>
  <?php
}
?>
</select>
<?php
}
}
?>
<?php
///////////////////////////////////////////////////////////////////////////////////////////////////
//ライブラリの出力用ショートコード設定
function library_cat_list_push(){
echo "<div class='library-cat'><ul>";
$args = array(
	'taxonomy' => 'librarycat',
	'orderby' => 'slug',
	'hide_empty' => 0,
	'title_li' => ( '<h6>ライブラリーフォルダ</h6>' )
);
echo wp_list_categories( $args );
echo "</ul></div>";}
add_shortcode('libcat', 'library_cat_list_push');
////////////////////////////////////////////////////////////////////////////////////////////////////
//ライブラリの出力用テンプレートコード設定
function library_cat_list(){ ?>
<div class='library-file'>
<table>
<tr>
  <th>タイトル</th>
  <th>ファイル形式</th>
  <th>ファイルサイズ</th>
  <th>更新日時</th>
  <th>投稿者</th>
</tr>
<?php
// ライブラリ記事のループ出力 =======================================================================
global $wp_query;
global $post;
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
$taxonomy = $wp_query->get_queried_object();
$args = array(
	  'posts_per_page' => -1,
	  'tax_query' => array(
		'relation' => 'AND',
		  array(
			'taxonomy' => 'librarycat',
			'include_children' => false,
			'operator' => 'IN',
			'terms'=> $taxonomy->slug,
			'field'=>'slug'
		  )
		)
);
$loop = new WP_Query($args);
if ( $loop-> have_posts() ) : while ( $loop->have_posts() ) : $loop->the_post();
// ファイルのURL取得 ================================================================================
$document = get_field("document", $post->ID);
// ファイルのリンク・タイトル =======================================================================
echo "<tr><td><a href='". $document . "' target='_blank'>";
echo the_title();
echo "</a></td>";
// ファイル形式 =====================================================================================
echo "<td>";
echo substr(strrchr($document, '.'), 1);
echo "</td>";
// ファイルサイズ ===================================================================================
echo "<td>";
$docpath = str_replace(esc_url(home_url('/')),ABSPATH,$document);
$s = filesize($docpath);
$s = $s / 1024;
echo round($s,1) . "KB";
echo "</td>";
// 更新日時 =========================================================================================
echo "<td>";
$mtime = get_the_modified_time('Y/n/j H：i');//更新日時
$ptime = get_the_time('Y/n/j H：i');//公開日時
if($mtime > $ptime)//更新日時の方が新しい場合
	echo $mtime;
	else if($mtime < $ptime)//公開日時の方が新しい場合
		echo $ptime;
	else
		echo $ptime;
echo "</td>";
// 投稿者名 =========================================================================================
echo "<td>";
$last_name = get_the_author_meta('last_name'); //姓取得
$firstname = get_the_author_meta('first_name'); //名取得
$nickname =  get_the_author_meta('nickname'); //ニックネーム取得
$unknown =  get_the_author_meta('user_login'); //ユーザーID取得
if($last_name && $firstname){
	echo $last_name.$firstname;
}else if($last_name){
	echo $last_name;
}else if($nickname){
	echo $nickname;
}else{
	echo $unknown;
	}
echo "</td>";
echo "</tr>";
endwhile;
else:
echo "<tr><td colspan='5'>このフォルダにはファイルがありません。</td></tr>";
endif;
echo "</table>";
echo "</div>";
}
add_shortcode('liblist', 'library_cat_list');
//////////////////////////////////////////////////////////////////////////////////////////////////////
//jqueryでのフォルダ表示非表示を追加
function add_script() {
    wp_register_script( 'jquery_min_js', 'http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js', array(), NULL, false );
    wp_register_script( 'style_js', plugins_url('style.js', __FILE__), array(), NULL, false );
    wp_enqueue_script('jquery_min_js');
    wp_enqueue_script('style_js');
}
add_action('wp_enqueue_scripts', 'add_script'); 
?>