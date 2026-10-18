<?php
/** CLI-memory-only project preservation probes. Private export arrives via stdin. */
if ( ! defined('WP_CLI') || ! WP_CLI ) exit(1);
global $wpdb, $wp_query, $post;
$checks=0;
$check=static function($condition,$label)use(&$checks){if(!$condition)WP_CLI::error('Projects: '.$label);++$checks;};
$snapshot=static function()use($wpdb){return array($wpdb->get_results("SELECT * FROM {$wpdb->posts} ORDER BY ID",ARRAY_A),$wpdb->get_results("SELECT * FROM {$wpdb->postmeta} ORDER BY meta_id",ARRAY_A));};
$baseline_rows=$snapshot();
$private=json_decode(file_get_contents('php://stdin'),true);
if(empty($private['pages'])||!is_array($private['pages']))WP_CLI::error('A private source export must arrive via stdin, never a web-root file.');
$records=array_column($private['pages'],null,'path');unset($private);
$guides=Yoona_SEO_Project_Guides::data();
$patches=array_column(json_decode(file_get_contents(dirname(YOONA_SEO_FILE).'/content/changes.json'),true)['pages'],null,'path');
$check(count($guides)===150,'150 reviewed factual project guides are available.');
$seed=get_post((int)get_option('page_on_front'),ARRAY_A);$fixture_id=960000;
$set=static function($path,$content,$overrides=array())use(&$fixture_id,$seed,$records,&$post,&$wp_query){
 ++$fixture_id;$record=$seed;$record['ID']=$fixture_id;$record['post_name']=trim($path,'/');$record['post_parent']=0;
 $record['post_title']=isset($records[$path])?$records[$path]['post_title']:'Synthetic manual project';$record['post_content']=$content;
 $record['post_type']='post';$record['post_status']='publish';$record['post_password']='';
 foreach($overrides as$key=>$value)$record[$key]=$value;
 $post=new WP_Post((object)$record);wp_cache_set($fixture_id,$post,'posts');
 wp_cache_set($fixture_id,array('_et_pb_use_builder'=>array(strpos($content,'[et_pb_')!==false?'on':'off')),'post_meta');
 $wp_query=new WP_Query();$wp_query->is_single=true;$wp_query->is_singular=true;$wp_query->queried_object=$post;$wp_query->queried_object_id=$fixture_id;
 $wp_query->post=$post;$wp_query->posts=array($post);$wp_query->post_count=1;$wp_query->current_post=0;setup_postdata($post);
 return$fixture_id;
};
$reset=static function(){$GLOBALS['gallery_fixture_callbacks']=array();$GLOBALS['company_fixture_callbacks']=array();};
// Late core image performance heuristics keep request-global counters. Compare
// the full source tree before those late attribute-only filters, then assert the
// final browser-facing photo sequence/count separately.
$render=static function($post_id,$via_project=false){
 $before_images=null;$capture=static function($content)use(&$before_images){$before_images=$content;return$content;};
 add_filter('the_content',$capture,11);
 try{
  if($via_project)$result=Yoona_SEO_Project_Guides::original($post_id);
  else{ob_start();try{Yoona_SEO_Content_Cleanup::the_content();$result=array('html'=>ob_get_contents());}finally{ob_end_clean();}}
  $result['before_images']=$before_images;return$result;
 }finally{remove_filter('the_content',$capture,11);}
};
// Native gallery block serials increment across renders in one CLI request.
$normalize=static function($html){return preg_replace('/\bwp-block-gallery-[1-9][0-9]*\b/','wp-block-gallery-INSTANCE',$html);};
$images=static function($html){$dom=new DOMDocument();$previous=libxml_use_internal_errors(true);$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html,LIBXML_NONET);libxml_clear_errors();libxml_use_internal_errors($previous);$sources=array();foreach($dom->getElementsByTagName('img')as$img)$sources[]=$img->getAttribute('src');return$sources;};
$clear=static function($id){wp_cache_delete($id,'posts');wp_cache_delete($id,'post_meta');};
$versions=0;$source_photo_counts=array();$planning=array();
foreach($guides as$path=>$guide){
 $check(isset($records[$path])&&$records[$path]['post_type']==='post','Guide remains bound to a real source post.');
 $check($guide['title']===$records[$path]['post_title']&&!empty($guide['planning'])&&count($guide['related_paths'])>=1,'Project title/factual summary plus specific planning guidance and internal routes are retained.');
 $planning[$guide['service_path']]=$guide['planning'];$contents=array($records[$path]['post_content']);
 if(count($guide['source_sha256'])>1){$patched=Yoona_SEO_Divi::prepare($contents[0],$patches[$path]['divi_text']);$check(!is_wp_error($patched),'Frozen reviewed SEO source can be reproduced.');$contents[]=$patched;}
 foreach($contents as$content){
  $id=$set($path,$content);$context=Yoona_SEO_Project_Guides::context($id);
  $check(is_array($context)&&$context['path']===$path,'Original/reviewed source resolves exact project guard.');
  $reset();$expected=$render($id);$expected_callbacks=array($GLOBALS['gallery_fixture_callbacks'],$GLOBALS['company_fixture_callbacks']);
  $reset();$original=$render($id,true);
  $check(is_string($expected['before_images'])&&$normalize($original['before_images'])===$normalize($expected['before_images']),'Complete original callback output stays byte-identical before late core image attributes, with parent rows, captions, FAQ, links and other modules. '.$path);
  $check(array($GLOBALS['gallery_fixture_callbacks'],$GLOBALS['company_fixture_callbacks'])===$expected_callbacks,'Each original shortcode callback executes exactly once.');
  $source_images=$images($expected['html']);$check($images($original['html'])===$source_images&&$original['photo_count']===count($source_images),'Every photograph remains once and in original order; presentation count equals rendered DOM.');
  $source_photo_counts[count($source_images)]=isset($source_photo_counts[count($source_images)])?$source_photo_counts[count($source_images)]+1:1;
  ob_start();Yoona_SEO_Project_Guides::render($context);$facts=ob_get_clean();
  ob_start();Yoona_SEO_Project_Guides::render_planning($context);$general=ob_get_clean();
  $check(strpos($facts,esc_html($guide['summary']))!==false&&strpos($facts,'yn-project-summary')!==false,'Unique factual project introduction appears once.');
  $check(strpos($general,esc_html($guide['planning']))!==false&&strpos($general,'Warmia i Mazury')!==false,'General regional planning guidance is separate from completed-job facts.');
  ++$versions;$clear($id);
 }
 $manual=$records[$path]['post_content']."\n<p>MANUAL_PROJECT_TAIL_35</p>";$id=$set($path,$manual);
 $check(Yoona_SEO_Project_Guides::context($id)===false,'Manual source changes decline stale curated copy.');
 $reset();$expected=$render($id);$reset();$original=$render($id,true);
 $check($normalize($original['before_images'])===$normalize($expected['before_images'])&&strpos($original['html'],'MANUAL_PROJECT_TAIL_35')!==false,'Manual source keeps the complete current source with premium presentation.');$clear($id);
}
$check($versions===160,'All 150 original and ten reviewed SEO source variants passed.');
$check(count($planning)===26&&count(array_unique($planning))===26,'26 relevant service-specific planning paragraphs avoid one repeated generic project description.');
$native='<h1>Manual source heading</h1><p>UNRECOGNIZED_SOURCE_COPY</p><figure><a href="/contact-destination/"><img src="/manual-project.jpg" alt="Manual photograph"></a><figcaption>Original caption</figcaption></figure><form><label>Original request<input name="project-request"></label></form><details><summary>Question</summary>Original answer</details>';
$id=$set('/unrecognized-manual-project/',$native);$check(Yoona_SEO_Project_Guides::context($id)===false,'An unknown project receives no curated facts.');$reset();$expected=$render($id);$reset();$original=$render($id,true);
$check($normalize($original['before_images'])===$normalize($expected['before_images'])&&$original['photo_count']===1&&$original['has_h1'],'Unknown single-photo project preserves original title, non-image destination link, caption, form and accordion.');$clear($id);
$id=$set('/unrecognized-manual-project/','<p>Image-free editorial project.</p>');$original=Yoona_SEO_Project_Guides::original($id);$check($original['photo_count']===0&&strpos($original['html'],'Image-free editorial project.')!==false,'Image-free entries keep their original text without a fabricated photograph.');$clear($id);
foreach(array('post_password'=>'secret','post_status'=>'draft','post_type'=>'page')as$field=>$value){$id=$set('/unrecognized-manual-project/',$native,array($field=>$value));$original=Yoona_SEO_Project_Guides::original($id);$check($original['html']===''&&$original['photo_count']===0,'Nonpublic, protected or nonproject content is not exposed.');$clear($id);}
$id=$set('/unrecognized-manual-project/',$native);$prior_buffers=ob_get_level();$throw=static function($content){throw new RuntimeException('project-render-probe');};add_filter('the_content',$throw,19);$caught=false;
try{Yoona_SEO_Project_Guides::original($id);}catch(RuntimeException$error){$caught=$error->getMessage()==='project-render-probe';}finally{remove_filter('the_content',$throw,19);}
$check($caught&&ob_get_level()===$prior_buffers,'Renderer restores its output buffer and propagates real callback failures.');$clear($id);
$check($snapshot()===$baseline_rows,'All memory-only probes preserved every stored post and metadata row.');
ksort($source_photo_counts);WP_CLI::log('Observed rendered photo counts by source version: '.wp_json_encode($source_photo_counts));
WP_CLI::success($checks.' project preservation/copy/source-guard assertions passed for '.$versions.' source versions. Private source stayed in stdin/process memory, never in HTTP or repository files. Licensed Divi is unavailable; tests use original stored shortcodes with synthetic callbacks.');
