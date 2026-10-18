<?php
/** Synthetic public offers; actual client source is only accepted in CLI memory. */
if (!defined('WP_CLI') || !WP_CLI || !isset($args[0])) exit(1);
$mode = $args[0];
global $wpdb;
$snapshot = static function () use ($wpdb) { return array($wpdb->get_results("SELECT * FROM {$wpdb->posts} ORDER BY ID", ARRAY_A), $wpdb->get_results("SELECT * FROM {$wpdb->postmeta} ORDER BY meta_id", ARRAY_A)); };
if ($mode === 'snapshot') { update_option('yoona_gallery_snapshot', $snapshot(), false); WP_CLI::success('All post and metadata rows captured.'); return; }
if ($mode === 'verify') { if ($snapshot() !== get_option('yoona_gallery_snapshot')) WP_CLI::error('Frontend changed post or metadata rows.'); WP_CLI::success('Every post and metadata row stayed byte-identical.'); return; }
if ($mode === 'private') {
 if (empty($args[1]) || !is_readable($args[1])) WP_CLI::error('CLI-only private export required.');
 if (!method_exists('Yoona_SEO_Service_Guides','legacy_content')) WP_CLI::error('The gallery repair API is unavailable.');
 global $wp_query, $post;
 $baseline_rows=$snapshot();
 $records=array_column(json_decode(file_get_contents($args[1]),true)['pages'],null,'path');
 $patches=array_column(json_decode(file_get_contents(dirname(YOONA_SEO_FILE).'/content/changes.json'),true)['pages'],null,'path');
 $definitions=Yoona_SEO_Design::data()['offer_heroes'];
 $seed=get_post((int)get_option('page_on_front'),ARRAY_A); $memory_id=810000; $checks=0; $versions=0;
 $check=static function($condition,$label)use(&$checks){if(!$condition)WP_CLI::error('Gallery private: '.$label);++$checks;};
 $set=static function($path,$body)use(&$post,&$wp_query,&$memory_id,$records,$seed){
  ++$memory_id;$row=$seed;$row['ID']=$memory_id;$row['post_name']=trim($path,'/');$row['post_parent']=0;$row['post_title']=$records[$path]['post_title'];$row['post_content']=$body;$row['post_type']='page';$row['post_status']='publish';$row['post_password']='';
  $post=new WP_Post((object)$row);wp_cache_set($memory_id,$post,'posts');wp_cache_set($memory_id,array('_et_pb_use_builder'=>array('on')),'post_meta');$wp_query=new WP_Query();$wp_query->is_page=true;$wp_query->is_singular=true;$wp_query->queried_object=$post;$wp_query->queried_object_id=$memory_id;$wp_query->post=$post;$wp_query->posts=array($post);$wp_query->post_count=1;$wp_query->current_post=0;setup_postdata($post);return$memory_id;
 };
 $render=static function($hero=false){ob_start();try{Yoona_SEO_Content_Cleanup::the_content($hero);return ob_get_contents();}finally{ob_end_clean();}};
 $details='<section class="repair-curated-details">REPAIR_CURATED_DETAILS</section>';
 foreach($definitions as$path=>$definition){
  $source=$records[$path]['post_content'];$prepared=Yoona_SEO_Divi::prepare($source,$patches[$path]['divi_text']);$check(!is_wp_error($prepared),'Frozen SEO patches reproduce '.$path);
  foreach(array($source,$prepared)as$version=>$body){
   $id=$set($path,$body);$guide=Yoona_SEO_Service_Guides::context($id);$check(is_array($guide),'Guide resolves source version '.$path);
   $sections=array();$collector=static function($output,$tag,$atts,$match)use(&$sections){if($tag==='et_pb_section')$sections[]=array('html'=>unescape_invalid_shortcodes($output),'raw'=>$match[0]);return$output;};
   $GLOBALS['gallery_fixture_callbacks']=array();add_filter('do_shortcode_tag',$collector,PHP_INT_MAX,4);try{$original=$render();}finally{remove_filter('do_shortcode_tag',$collector,PHP_INT_MAX);} $original_callbacks=$GLOBALS['gallery_fixture_callbacks'];
   // Compare callback bytes before core wp_filter_content_tags (priority 12)
   // adds position-dependent image decoding/loading/srcset attributes. This
   // capture is registered after core do_shortcode at the same priority 11.
   // Browser probes separately verify the final gallery DOM and its CSS.
   $before_image_filters=null;$capture_calls=0;$capture=static function($html)use(&$before_image_filters,&$capture_calls,$id){if((int)get_the_ID()===$id){$before_image_filters=$html;++$capture_calls;}return$html;};
   add_filter('the_content',$capture,11);
   $GLOBALS['gallery_fixture_callbacks']=array();try{$result=Yoona_SEO_Service_Guides::legacy_content($id,$guide,$details);}finally{remove_filter('the_content',$capture,11);}
   $check($capture_calls===1&&is_string($before_image_filters),'The complete shortcode output is captured once before late core image filters '.$path);
   $check($result['hero_applied']&&$result['intro_applied'],'Known hero and introduction apply '.$path.' (variant='.$version.', hero='.(int)$result['hero_applied'].', intro='.(int)$result['intro_applied'].', observed_intro_sha256='.(isset($sections[1]['raw'])?hash('sha256',$sections[1]['raw']):'missing').')');
   $check(substr_count($result['html'],'REPAIR_CURATED_DETAILS')===1,'Reviewed details appear once '.$path);
   $check($GLOBALS['gallery_fixture_callbacks']===$original_callbacks,'Every original callback runs exactly once '.$path);
   $check(count($sections)>=2,'Original complete sections were rendered '.$path);
   $position=0;$retained=0;
   foreach(array_slice($sections,2)as$section){if($section['html']==='')continue;$found=strpos($before_image_filters,$section['html'],$position);$check($found!==false,'A complete lower section including all indexed CSS classes survives byte-identically and in order before late core image filters '.$path);$position=$found+strlen($section['html']);++$retained;}
   $check($retained>0,'At least one complete lower section was verified '.$path);
   if($definition['service_intro_mode']==='append')$check(strpos($before_image_filters,$sections[1]['html'])!==false,'Special introduction with original media remains complete before late core image filters '.$path);
   else $check(strpos($before_image_filters,$sections[1]['html'])===false,'Only reviewed text introduction is replaced '.$path);
   ++$versions;wp_cache_delete($id,'posts');wp_cache_delete($id,'post_meta');
  }
  // A later intro edit must retain that whole output, with no stale replacement.
  $end=strpos($source,'[/et_pb_section]')+strlen('[/et_pb_section]');$intro_start=strpos($source,'[et_pb_section',$end);$intro_open_end=strpos($source,']',$intro_start)+1;
  $changed=substr_replace($source,'<!-- later manual introduction edit -->',$intro_open_end,0);$id=$set($path,$changed);$guide=Yoona_SEO_Service_Guides::context($id);
  $GLOBALS['gallery_fixture_callbacks']=array();$expected=$render(Yoona_SEO_Offer_Hero::context($id));$GLOBALS['gallery_fixture_callbacks']=array();$result=Yoona_SEO_Service_Guides::legacy_content($id,$guide,$details);
  $check($result['hero_applied']&&!$result['intro_applied']&&$result['html']===$expected,'Manual introduction falls back to byte-identical complete source output '.$path);wp_cache_delete($id,'posts');wp_cache_delete($id,'post_meta');
  $changed=substr_replace($source,'<!-- later manual hero edit -->',strpos($source,']')+1,0);$id=$set($path,$changed);$guide=Yoona_SEO_Service_Guides::context($id);$GLOBALS['gallery_fixture_callbacks']=array();$expected=$render();$GLOBALS['gallery_fixture_callbacks']=array();$result=Yoona_SEO_Service_Guides::legacy_content($id,$guide,$details);
  $check(!$result['hero_applied']&&!$result['intro_applied']&&$result['html']===$expected,'Manual hero falls back to byte-identical complete source output '.$path);wp_cache_delete($id,'posts');wp_cache_delete($id,'post_meta');
 }
 // Throw from an already registered shortcode filter. Both temporary renderer
 // filters and its output buffer must be removed without touching prior hooks.
 $path=array_key_first($definitions);$id=$set($path,$records[$path]['post_content']);$guide=Yoona_SEO_Service_Guides::context($id);
 $prior_calls=0;$prior_filter=static function($output,$tag)use(&$prior_calls){if($tag==='et_pb_text')++$prior_calls;return$output;};
 $throw_filter=static function($output,$tag){if($tag==='et_pb_text')throw new RuntimeException('synthetic-gallery-render-exception');return$output;};
 add_filter('do_shortcode_tag',$prior_filter,6,2);add_filter('do_shortcode_tag',$throw_filter,19,2);
 global $wp_filter;$prior_hooks=$wp_filter['do_shortcode_tag']->callbacks;$prior_buffers=ob_get_level();$caught=false;
 try{
  try{Yoona_SEO_Service_Guides::legacy_content($id,$guide,$details);}catch(RuntimeException$error){$caught=$error->getMessage()==='synthetic-gallery-render-exception';}
  $check($caught&&$prior_calls>0,'A real shortcode callback exception propagates after pre-existing filters run.');
  $check($wp_filter['do_shortcode_tag']->callbacks===$prior_hooks,'Temporary intro and hero filters are removed on an exception; every previously registered filter survives unchanged.');
  $check(ob_get_level()===$prior_buffers,'An exceptional render restores the original output-buffer depth.');
  remove_filter('do_shortcode_tag',$throw_filter,19);$prior_hooks=$wp_filter['do_shortcode_tag']->callbacks;$GLOBALS['gallery_fixture_callbacks']=array();
  $result=Yoona_SEO_Service_Guides::legacy_content($id,$guide,$details);
  $check($result['intro_applied']&&substr_count($result['html'],'REPAIR_CURATED_DETAILS')===1&&$wp_filter['do_shortcode_tag']->callbacks===$prior_hooks,'The next render applies details once and leaves prior filters intact after the earlier exception.');
 }finally{remove_filter('do_shortcode_tag',$throw_filter,19);remove_filter('do_shortcode_tag',$prior_filter,6);wp_cache_delete($id,'posts');wp_cache_delete($id,'post_meta');}
 $check($versions===82,'All 41 original and 41 frozen SEO body variants passed.');
 $check($snapshot()===$baseline_rows,'All private source tests stayed in memory and preserved every post / metadata row.');
 WP_CLI::success($checks.' memory-only gallery / complete lower-section assertions passed for '.$versions.' offer versions. No private source HTML was printed or installed.');return;
}
if ($mode === 'owned') {
 // This mode is restricted to this suite's disposable, loopback fixture site.
 if(count((array)get_option('yoona_gallery_fixture_ids'))!==41||!preg_match('~\Ahttp://127\.0\.0\.1:\d+/?\z~',get_option('siteurl')))WP_CLI::error('Owned-page probes require this suite\'s disposable fixture site.');
 global $post,$wp_query;$checks=0;$check=static function($condition,$label)use(&$checks){if(!$condition)WP_CLI::error('Gallery owned: '.$label);++$checks;};
 $admins=get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID'));if(!$admins)WP_CLI::error('A disposable publishing administrator is required.');wp_set_current_user((int)$admins[0]);
 $before=$snapshot();$installed=Yoona_SEO_Growth_Pages::install();
 $check(!is_wp_error($installed)&&empty($installed['errors']),'The existing six-page installer completes without errors.');
 $created=is_array($installed)&&isset($installed['created'])?array_map('intval',$installed['created']):array();
 $after=$snapshot();$retained_posts=array_values(array_filter($after[0],static function($row)use($created){return!in_array((int)$row['ID'],$created,true);}));$retained_meta=array_values(array_filter($after[1],static function($row)use($created){return!in_array((int)$row['post_id'],$created,true);}));
 $check(array($retained_posts,$retained_meta)===$before,'Adding owned fixtures preserves every pre-existing post and metadata row.');
 $paths=Yoona_SEO_Growth_Pages::PATHS;$check(count($paths)===6,'Exactly six specialist owned-page paths are checked.');
 foreach($paths as$path){
  $record=get_page_by_path(trim($path,'/'),OBJECT,'page');$check($record&&get_post_meta($record->ID,'_yoona_growth_owned',true)==='3.4.0','The installer owns the expected specialist page '.$path);
  $wp_query=new WP_Query(array('page_id'=>$record->ID,'post_type'=>'page'));$post=get_post($record->ID);setup_postdata($post);$guide=Yoona_SEO_Service_Guides::context($record->ID);
  $check(is_array($guide),'The unchanged owned source and title pass their guards '.$path);
  $GLOBALS['gallery_fixture_callbacks']=array();$details='<section class="repair-owned-details">REPAIR_OWNED_DETAILS</section>';$result=Yoona_SEO_Service_Guides::legacy_content($record->ID,$guide,$details);
  $check($result['html']===$details&&$result['hero_applied']&&$result['intro_applied'],'Owned details render exactly once without duplicated stored fallback HTML '.$path);
  $check($GLOBALS['gallery_fixture_callbacks']===array()&&strpos($result['html'],'yn-guide-media-grid')===false,'Owned pages execute no source shortcodes and introduce no gallery extraction grid '.$path);
  foreach(array('post_content','post_title','post_status','post_password')as$field){
   $changed=clone$record;$changed->$field=$field==='post_status'?'draft':$record->$field.' synthetic later edit';wp_cache_set($record->ID,$changed,'posts');
   $check(Yoona_SEO_Service_Guides::context($record->ID)===false,'A later owned '.$field.' change preserves the normal source fallback '.$path);wp_cache_set($record->ID,$record,'posts');
  }
 }
 $check($snapshot()===$after,'Owned render and guard probes do not change any post or metadata row.');
 WP_CLI::success($checks.' owned specialist-page installer / single-render / manual-edit guard assertions passed.');return;
}
if (!in_array($mode,array('prepare','refresh-fixture'),true)) WP_CLI::error('Unknown gallery test mode.');
$mu = <<<'MU'
<?php
/** Synthetic Divi callbacks preserve its structural contract; not licensed Divi. */
function et_pb_is_pagebuilder_used($id) { return get_post_meta($id, '_et_pb_use_builder', true) === 'on'; }
add_filter('the_content', function ($content) { if (et_pb_is_pagebuilder_used(get_the_ID())) { remove_filter('the_content', 'wpautop'); remove_filter('the_content', 'shortcode_unautop'); } return $content; }, 8);
add_action('wp_head', function () { echo '<style id="gallery-fixture-css">.repair-original-tail .et_pb_row{display:flex;gap:24px;max-width:1200px;width:90%;margin:auto}.repair-original-tail .et_pb_column{min-width:0;flex:1}.repair-original-tail .et_pb_gallery .et_pb_gallery_items{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.repair-original-tail img{display:block;width:100%;height:auto}.repair-original-tail .et_pb_cta{padding:24px;background:#eee}.repair-original-tail .et_pb_social_media_follow{display:flex;gap:16px}.repair-original-tail{padding:32px 0}@media(max-width:600px){.repair-original-tail .et_pb_row{display:block}.repair-original-tail .et_pb_gallery .et_pb_gallery_items{grid-template-columns:repeat(2,minmax(0,1fr))}}</style>'; });
// Public tag names only: cover every actual module type in the 41 offers.
foreach (array('et_pb_section','et_pb_row','et_pb_column','et_pb_row_inner','et_pb_column_inner','et_pb_text','et_pb_gallery','et_pb_button','et_pb_image','et_pb_divider','et_pb_toggle','et_pb_social_media_follow','et_pb_social_media_follow_network','et_pb_fullwidth_header','et_pb_cta','et_pb_contact_form') as $tag) {
 add_shortcode($tag, function ($atts, $content = '', $tag = '') {
  if (!isset($GLOBALS['gallery_fixture_callbacks'][$tag])) $GLOBALS['gallery_fixture_callbacks'][$tag] = 0;
  $index=$GLOBALS['gallery_fixture_callbacks'][$tag]++;
  $class = $tag . ' ' . $tag . '_' . $index . (!empty($atts['module_class']) ? ' ' . $atts['module_class'] : '');
  if ($tag === 'et_pb_gallery') return '<div class="et_pb_module ' . esc_attr($class) . ' repair-gallery"><div class="et_pb_gallery_items"><div class="et_pb_gallery_item"><a href="/gallery-one.jpg"><img src="/gallery-one.jpg" width="600" height="420" alt="Synthetic gallery one"></a><h3>Gallery one</h3></div><div class="et_pb_gallery_item"><a href="/gallery-two.jpg"><img src="/gallery-two.jpg" width="600" height="420" alt="Synthetic gallery two"></a><h3>Gallery two</h3></div><div class="et_pb_gallery_item"><a href="/gallery-three.jpg"><img src="/gallery-three.jpg" width="600" height="420" alt="Synthetic gallery three"></a><h3>Gallery three</h3></div></div></div>';
  if ($tag === 'et_pb_image') return '<div class="et_pb_module ' . esc_attr($class) . '"><img src="' . esc_url(isset($atts['src'])?$atts['src']:'') . '" alt="Synthetic retired section image"></div>';
  if ($tag === 'et_pb_cta') return '<div class="et_pb_module ' . esc_attr($class) . ' repair-cta"><h2>REPAIR ORIGINAL CTA</h2><a href="/kontakt/">Ask about this service</a></div>';
  if ($tag === 'et_pb_toggle') return '<div class="et_pb_module ' . esc_attr($class) . ' repair-toggle"><h3>REPAIR ORIGINAL TOGGLE</h3><div class="et_pb_toggle_content">Original detailed answer</div></div>';
  if ($tag === 'et_pb_social_media_follow') return '<ul class="et_pb_module ' . esc_attr($class) . ' repair-social">' . (trim($content)!==''?do_shortcode($content):'<li><a href="https://example.invalid/social-one">Social one</a></li><li><a href="https://example.invalid/social-two">Social two</a></li>') . '</ul>';
  if ($tag === 'et_pb_contact_form') return '<div class="et_pb_module ' . esc_attr($class) . ' et_pb_contact_form_container repair-form"><form><label>Original request<input name="repair-name" autocomplete="off"></label><button type="button">Send</button></form></div>';
  return '<div class="' . esc_attr($class) . '">' . do_shortcode($content) . '</div>';
 });
}
add_action('wp_footer', function () { echo '<script id="repair-callback-counts" type="application/json">' . wp_json_encode(isset($GLOBALS['gallery_fixture_callbacks']) ? $GLOBALS['gallery_fixture_callbacks'] : array()) . '</script>'; });
MU;
wp_mkdir_p(WPMU_PLUGIN_DIR);
if (false === file_put_contents(WPMU_PLUGIN_DIR . '/zz-gallery-repair-fixture.php', $mu)) WP_CLI::error('Cannot write synthetic fixture callbacks.');
if($mode==='refresh-fixture'){WP_CLI::success('Synthetic module fixture refreshed without changing any page or metadata row.');return;}
$home = wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_name'=>'home','post_title'=>'Home','post_content'=>'<p>Public synthetic homepage.</p>'));
update_option('show_on_front','page'); update_option('page_on_front',$home); update_option('permalink_structure','/%postname%/');
$ids = array();
foreach (array_keys(Yoona_SEO_Design::data()['offer_heroes']) as $path) {
 $guide = Yoona_SEO_Service_Guides::data()[$path];
 $source = '[et_pb_section module_class="repair-original-intro"][et_pb_row][et_pb_column][et_pb_text]<h1>OLD OFFER INTRODUCTION</h1><p>Original introductory prose.</p>[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section]';
 $source .= '[et_pb_section module_class="repair-original-tail repair-gallery-section"][et_pb_row module_class="repair-gallery-row"][et_pb_column module_class="repair-gallery-column"][et_pb_text]<h2>Nasze realizacje</h2>[/et_pb_text][et_pb_gallery][/et_pb_gallery][/et_pb_column][/et_pb_row][/et_pb_section]';
 $source .= '[et_pb_section module_class="repair-original-tail repair-bottom-section"][et_pb_row module_class="repair-bottom-row"][et_pb_column module_class="repair-bottom-left"][et_pb_cta][/et_pb_cta][et_pb_toggle][/et_pb_toggle][/et_pb_column][et_pb_column module_class="repair-bottom-right"][et_pb_social_media_follow][/et_pb_social_media_follow][et_pb_contact_form][/et_pb_contact_form][/et_pb_column][/et_pb_row][/et_pb_section]';
 $source .= '[et_pb_section module_class="repair-retired-contact"][et_pb_row][et_pb_column][et_pb_image src="https://yoona.pl/wp-content/uploads/2023/09/agencja-reklamowa-olsztyn.png"][/et_pb_image][/et_pb_column][et_pb_column][et_pb_text]<h2>MASZ PYTANIE? KONTAKT</h2><p>Retired contact section</p>[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section]';
 $id = wp_insert_post(wp_slash(array('post_type'=>'page','post_status'=>'publish','post_name'=>trim($path,'/'),'post_title'=>$guide['title'],'post_content'=>$source)),true);
 if (is_wp_error($id)) WP_CLI::error('Could not insert synthetic service.');
 update_post_meta($id,'_et_pb_use_builder','on'); update_post_meta($id,'rank_math_title','REPAIR NATIVE '.$guide['title']); update_post_meta($id,'rank_math_description','Native Rank Math service description.'); $ids[$path]=$id;
}
foreach (array('kontakt'=>'Kontakt','pelna-oferta'=>'Pełna oferta') as $slug=>$title) wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_name'=>$slug,'post_title'=>$title,'post_content'=>'<p>Public synthetic auxiliary page.</p>'));
update_option('yoona_gallery_fixture_ids',$ids,false);
WP_CLI::success('41 synthetic service pages retain real Divi section, row and column contracts, gallery, CTA, toggle, social links, form and retired section.');
