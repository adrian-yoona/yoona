<?php
/** Public synthetic layout fixtures, never original/private customer content. */
if (!defined('WP_CLI') || !WP_CLI || !preg_match('~\Ahttp://127\.0\.0\.1:\d+/?\z~',get_option('siteurl')) || count((array)get_option('yoona_gallery_fixture_ids'))!==41) WP_CLI::error('Disposable loopback gallery site required.');
$single='[et_pb_section module_class="project35-source"][et_pb_row][et_pb_column type="1_2"][et_pb_image src="/wp-content/uploads/yoona35-project-single.jpg" alt="Public synthetic single photograph"][/et_pb_image][/et_pb_column][et_pb_column type="1_2"][et_pb_text]<h2>Oryginalny opis realizacji testowej</h2><p>PUBLIC SINGLE SOURCE COPY — oryginalny opis, łącznik do kontaktu i galeria pozostają razem.</p><p><a href="/kontakt/">Przejdź do kontaktu</a></p>[/et_pb_text][et_pb_toggle title="Pytanie z oryginalnego modułu"]PUBLIC ORIGINAL ANSWER[/et_pb_toggle][/et_pb_column][/et_pb_row][/et_pb_section]';
$gallery='<h1>Publiczny projekt testowy z trzema zdjęciami</h1><p>PUBLIC GALLERY SOURCE COPY — kompletna natywna galeria zachowuje wszystkie podpisy.</p><figure class="wp-block-gallery has-nested-images columns-3 is-cropped">';
for($i=1;$i<=3;++$i)$gallery.='<figure class="wp-block-image size-large"><img src="/wp-content/uploads/yoona35-project-gallery-'.$i.'.jpg" alt="Public synthetic gallery photograph '.$i.'" width="1600" height="1100"><figcaption>Publiczny podpis zdjęcia '.$i.'</figcaption></figure>';
$gallery.='</figure><p><a href="/kontakt/">Oryginalny odnośnik do kontaktu</a></p><details><summary>Oryginalne pytanie</summary>PUBLIC ORIGINAL NATIVE ANSWER</details>';
$fixtures=array('/yoona35-project-detail-single/'=>array('title'=>'Publiczny projekt — jedno zdjęcie','source'=>$single,'photos'=>1,'builder'=>'on'),'/yoona35-project-detail-gallery3/'=>array('title'=>'Publiczny projekt — galeria trzech zdjęć','source'=>$gallery,'photos'=>3,'builder'=>'off'));
$mu = <<<'MU'
<?php
/** Only an explicit localhost browser-test request adds public synthetic guides. */
add_action('plugins_loaded',static function(){
 if((defined('WP_CLI')&&WP_CLI)||!isset($_GET['yoona_project_fixture'])||$_GET['yoona_project_fixture']!=='known'||!preg_match('~\Ahttp://127\.0\.0\.1:\d+/?\z~',get_option('siteurl')))return;
 $guides=Yoona_SEO_Project_Guides::data();
 foreach(array('yoona35-project-detail-single','yoona35-project-detail-gallery3')as$slug){
  $post=get_page_by_path($slug,OBJECT,'post');if(!$post||get_post_meta($post->ID,'_yoona_project35_fixture',true)!=='public-synthetic-v1')continue;
  $guides['/'.$slug.'/']=array('title'=>$post->post_title,'summary'=>'PUBLIC SYNTHETIC SUMMARY — Projekt pokazuje spójne oznakowanie i zachowuje wszystkie zdjęcia oraz podpisy z oryginalnej treści. To publiczny przykład testowy, a nie opis realizacji klienta.','scope'=>array('Przygotowanie czytelnego projektu graficznego','Produkcja elementów identyfikacji wizualnej','Montaż z zachowaniem oryginalnej konstrukcji'),'materials'=>array('Publiczny przykładowy materiał testowy'),'location'=>'Olsztyn — publiczny przykład testowy','service_path'=>'/reklama-na-rusztowaniu/','related_paths'=>array('/siatka-mesh/','/banery/'),'planning'=>'PUBLIC SYNTHETIC PLANNING — Do omówienia własnej realizacji przygotuj wymiary, materiały graficzne i zdjęcie miejsca montażu. Ustal zakres wykonania i sposób ekspozycji z zespołem przed rozpoczęciem produkcji.','source_sha256'=>array(hash('sha256',(string)$post->post_content)));
 }
 $property=new ReflectionProperty('Yoona_SEO_Project_Guides','data');$property->setAccessible(true);$property->setValue(null,$guides);
},100);
MU;
if(false===file_put_contents(WPMU_PLUGIN_DIR.'/zzzz-project35-browser-fixture.php',$mu))WP_CLI::error('Cannot write localhost-only synthetic guide fixture.');
if(isset($args[0])&&$args[0]==='mu-only'){WP_CLI::success('Only localhost HTTP fixture installed; no database writes.');return;}
$ids=array();
foreach($fixtures as$path=>$fixture){
 $existing=get_page_by_path(trim($path,'/'),OBJECT,'post');
 if($existing&&get_post_meta($existing->ID,'_yoona_project35_fixture',true)!=='public-synthetic-v1')WP_CLI::error('Refusing to replace an unrelated post.');
 $data=array('post_title'=>$fixture['title'],'post_name'=>trim($path,'/'),'post_type'=>'post','post_status'=>'publish','post_content'=>$fixture['source'],'post_date'=>'2020-01-01 12:00:00','comment_status'=>'closed');if($existing)$data['ID']=$existing->ID;
 $id=wp_insert_post($data,true);if(is_wp_error($id))WP_CLI::error($id);
 update_post_meta($id,'_yoona_project35_fixture','public-synthetic-v1');update_post_meta($id,'_et_pb_use_builder',$fixture['builder']);
 $ids[$path]=array('id'=>$id,'photos'=>$fixture['photos']);
}
update_option('yoona_project35_browser_fixtures',$ids,false);
WP_CLI::success('Two public synthetic project detail posts prepared before collection snapshots: '.wp_json_encode($ids));
