<?php
/** Disposable company-page probes. Private customer source is CLI-memory only. */
if (!defined('WP_CLI') || !WP_CLI || empty($args[0])) exit(1);
$mode = $args[0];
global $wpdb;
$snapshot = static function () use ($wpdb) { return array($wpdb->get_results("SELECT * FROM {$wpdb->posts} ORDER BY ID", ARRAY_A), $wpdb->get_results("SELECT * FROM {$wpdb->postmeta} ORDER BY meta_id", ARRAY_A)); };
if (!preg_match('~\Ahttp://127\.0\.0\.1:\d+/?\z~', get_option('siteurl')) || count((array)get_option('yoona_gallery_fixture_ids')) !== 41) WP_CLI::error('Company probes require the disposable loopback gallery site.');
if ($mode === 'snapshot') { update_option('yoona_company_snapshot', $snapshot(), false); WP_CLI::success('Company: all post and metadata rows captured after frontend warmup.'); return; }
if ($mode === 'verify') { if ($snapshot() !== get_option('yoona_company_snapshot')) WP_CLI::error('Company frontend changed post or metadata rows.'); WP_CLI::success('Company: every post and metadata row remained byte-identical.'); return; }
if ($mode === 'prepare') {
 $mu = <<<'MU'
<?php
/** TEST ONLY: synthetic public Divi contracts and in-memory hash overrides. */
function yoona_company_fixture_module($tag, $content, $class = '') {
 return '[' . $tag . ($class !== '' ? ' module_class="' . $class . '"' : '') . ']' . $content . '[/' . $tag . ']';
}
function yoona_company_fixture_sources() {
 $m = 'yoona_company_fixture_module';
 $column = static function ($html) use ($m) { return $m('et_pb_column', $html); };
 $row = static function ($html, $class = '') use ($m) { return $m('et_pb_row', $html, $class); };
 $text = static function ($html) use ($m) { return $m('et_pb_text', $html); };
 $section = static function ($html, $class = '') use ($m) { return $m('et_pb_section', $html, $class); };
 $parts = array('/o-nas/' => array(), '/kontakt/' => array());
 $parts['/o-nas/']['legacy-hero'] = array($section($row($column('') . $column($text('<h1 style="font-size:70px;color:#fff">Dawny nagłówek z pustą połową</h1>'))), 'company-old-hero'));
 $parts['/o-nas/']['intro'] = array($section($row($column($text('<h2>Agencja reklamowa, która produkuje</h2>')) . $column($text('<p style="text-align:justify;color:#fff;font-family:Roboto Condensed;font-size:22px">Projektujemy, drukujemy i montujemy reklamę. Pomagamy dobrać materiały, format i sposób realizacji.</p>'))), 'company-original-intro'));
 $faq = '';
 for ($i = 1; $i <= 8; ++$i) $faq .= '[et_pb_toggle title="Pytanie ' . $i . '"]Odpowiedź ' . $i . '. Szczegóły współpracy ustalamy przed realizacją.[/et_pb_toggle]';
 $parts['/o-nas/']['faq'] = array($section($row($column($text('<h2>Najczęstsze pytania</h2>')) . $column($faq)), 'company-original-faq'));
 $parts['/o-nas/']['service'] = array();
 foreach (array('Reklama zewnętrzna', 'Druk wielkoformatowy', 'Litery przestrzenne', 'Oznakowanie firm', 'Oklejanie samochodów', 'Materiały reklamowe', 'Montaż reklamy') as $index => $title) {
  $image = '[et_pb_image src="/company-photo-' . ($index + 1) . '.jpg" alt="Publiczne zdjęcie testowe ' . ($index + 1) . '"][/et_pb_image]';
  $copy = $text('<h2>' . $title . '</h2><p><span style="color:#fff;font-size:22px;font-family:Roboto Condensed">Od projektu po gotową realizację. Dobieramy materiały i przygotowujemy reklamę do miejsca ekspozycji.</span></p><p><a href="/pelna-oferta/">Zobacz ofertę</a></p>');
  $parts['/o-nas/']['service'][] = $section($row($column($index % 2 ? $copy : $image) . $column($index % 2 ? $image : $copy)), 'company-original-service');
 }
 $parts['/o-nas/']['values'] = array($section($row($column($text('<h2>Projekt, produkcja i montaż</h2><p style="color:white">Jeden zespół prowadzi projekt od ustalenia zakresu do gotowej realizacji.</p>'))), 'company-original-values'));
 $parts['/o-nas/']['narrative'] = array($section($m('et_pb_fullwidth_header', '<p>Obsługujemy Olsztyn, Ostródę i realizacje w Polsce.</p>'), 'company-original-narrative'), $section($m('et_pb_fullwidth_header', '<p>Zakres prac i materiały dobieramy do miejsca ekspozycji.</p>'), 'company-original-narrative'));
 $parts['/o-nas/']['closing'] = array($section($row($column($text('<h1>Porozmawiajmy o Twoim projekcie</h1><p><a href="/kontakt/">Kontakt z zespołem</a></p>'))), 'company-original-closing'));
 $social = $section($row($column('[et_pb_social_media_follow][et_pb_social_media_follow_network link="https://example.invalid/facebook"]Facebook[/et_pb_social_media_follow_network][et_pb_social_media_follow_network link="https://example.invalid/instagram"]Instagram[/et_pb_social_media_follow_network][/et_pb_social_media_follow]')), 'company-original-social');
 $parts['/o-nas/']['social'] = array($social);
 $parts['/kontakt/']['legacy-intro'] = array($row($column($text('<h1>Stary, rozbudowany kontakt</h1><p>Dawny wstęp.</p>')) . $column(''), 'company-old-contact-intro'));
 $office = static function ($city, $phone, $email, $suffix) use ($m) {
  return $m('et_pb_code', '<div style="font-family:Roboto Condensed,sans-serif;color:#000"><h3 style="font-size:24px">' . $city . '</h3><div style="padding:18px;border-radius:10px;box-shadow:0 8px 30px #ddd"><h4 style="font-size:20px">Biuro ' . $city . '</h4><p style="font-size:18px;display:flex;align-items:center;gap:8px"><svg width="18" height="18" aria-hidden="true"><circle cx="9" cy="9" r="7"/></svg><a href="tel:' . $phone . '">' . $phone . '</a></p><p style="font-size:18px;display:flex;align-items:center;gap:8px"><a href="mailto:' . $email . '">' . $email . '</a></p></div><div style="padding:18px;border-radius:10px;box-shadow:0 8px 30px #ddd"><h4 style="font-size:20px">Obsługa projektu ' . $suffix . '</h4><p>Uzgodnienia projektu, materiałów i terminu.</p></div></div>', 'company-office-' . $suffix);
 };
 $parts['/kontakt/']['contact-code'] = array($office('Olsztyn', '+48791144100', 'wyceny@yoona.pl', 'a'), $office('Ostróda', '+48896422399', 'biuro@yoona.pl', 'b'));
 $parts['/kontakt/']['office-row'] = array($row($column($parts['/kontakt/']['contact-code'][0]) . $column($parts['/kontakt/']['contact-code'][1]), 'company-original-offices'));
 $parts['/kontakt/']['map-code'] = array($m('et_pb_code', '<div style="width:100%;border-radius:12px;margin:24px 0"><iframe title="Mapa dojazdu do YOONA" src="https://www.google.com/maps?q=Ostr%C3%B3da&output=embed" width="100%" height="500" style="border:0" loading="lazy"></iframe><a class="company-original-route" href="https://maps.google.com/?q=Ostr%C3%B3da" style="display:inline-block;font-size:18px;color:#fff;background:#ff6600;padding:14px 30px;border-radius:8px">Wyznacz trasę</a></div>', 'company-original-map-code'));
 $parts['/kontakt/']['map-row'] = array($row($column($parts['/kontakt/']['map-code'][0]), 'company-original-map-row'));
 $parts['/kontakt/']['contact-main'] = array($section($parts['/kontakt/']['legacy-intro'][0] . $parts['/kontakt/']['office-row'][0] . $parts['/kontakt/']['map-row'][0], 'company-original-contact-main'));
 $parts['/kontakt/']['cta'] = array($section($row($column($text('<h2 style="font-size:9em;color:white">Stwórzmy coś razem</h2><p style="color:white">Prześlij projekt lub zdjęcie miejsca realizacji.</p>'))), 'company-original-cta'));
 $parts['/kontakt/']['review-code'] = array($m('et_pb_code', '[trustindex no-registration=public-synthetic-company]', 'company-original-review-code'));
 $parts['/kontakt/']['review-link'] = array($m('et_pb_code', '<a href="https://example.invalid/opinie" style="display:inline-block;font-size:18px;color:#fff;background:#ff6600;padding:14px 30px;border-radius:8px">Zobacz opinie</a>', 'company-original-review-link'));
 $parts['/kontakt/']['reviews'] = array($section($row($column($parts['/kontakt/']['review-code'][0] . $parts['/kontakt/']['review-link'][0])), 'company-original-reviews'));
 $parts['/kontakt/']['social'] = array($social);
 $form = $section($row($column('[et_pb_contact_form title="Zachowany formularz"][/et_pb_contact_form]')), 'company-manual-form-section');
 $sources = array('/o-nas/' => '', '/kontakt/' => '');
 foreach (array('legacy-hero','intro','faq','service','values','narrative','closing','social') as $role) $sources['/o-nas/'] .= implode('', $parts['/o-nas/'][$role]);
 foreach (array('contact-main','cta','reviews','social') as $role) $sources['/kontakt/'] .= implode('', $parts['/kontakt/'][$role]);
 $sources['/kontakt/'] .= $form;
 return array('parts' => $parts, 'sources' => $sources);
}
add_action('plugins_loaded', static function () {
 if (!class_exists('Yoona_SEO_Company_Pages')) return;
 $fixture = yoona_company_fixture_sources(); $data = Yoona_SEO_Company_Pages::data();
 foreach ($data as $path => &$definition) foreach (array('omit','annotate') as $mode) foreach ($definition[$mode] as &$entry) $entry['sha256'] = array_map(static function ($source) { return hash('sha256', $source); }, $fixture['parts'][$path][$entry['role']]);
 unset($definition, $entry);
 $property = new ReflectionProperty('Yoona_SEO_Company_Pages', 'data'); $property->setAccessible(true); $property->setValue(null, $data);
 // The callback deliberately keeps original parent/index classes and includes
 // inline legacy presentation. It is not a substitute for licensed Divi.
 foreach (array('et_pb_section','et_pb_row','et_pb_column','et_pb_row_inner','et_pb_column_inner','et_pb_text','et_pb_image','et_pb_toggle','et_pb_fullwidth_header','et_pb_social_media_follow','et_pb_social_media_follow_network','et_pb_code','et_pb_contact_form','trustindex') as $tag) {
  add_shortcode($tag, static function ($atts, $content = '', $tag = '') {
   if (!isset($GLOBALS['company_fixture_callbacks'][$tag])) $GLOBALS['company_fixture_callbacks'][$tag] = 0;
   $index = $GLOBALS['company_fixture_callbacks'][$tag]++;
   $class = $tag . ' ' . $tag . '_' . $index . (!empty($atts['module_class']) ? ' ' . $atts['module_class'] : '');
   $attribute = ' class="' . esc_attr($class) . '" data-company-callback="' . esc_attr($tag . '-' . $index) . '"';
   if ($tag === 'et_pb_image') return '<div' . $attribute . '><span class="et_pb_image_wrap"><img src="' . esc_url(isset($atts['src']) ? $atts['src'] : '/company-photo.jpg') . '" width="900" height="600" alt="' . esc_attr(isset($atts['alt']) ? $atts['alt'] : 'Public synthetic photo') . '"></span></div>';
   if ($tag === 'et_pb_fullwidth_header') return '<div' . $attribute . '><div class="et_pb_fullwidth_header_container"><div class="header-content"><h2></h2>' . do_shortcode($content) . '</div></div></div>';
   if ($tag === 'et_pb_code') return '<div' . $attribute . '><div class="et_pb_code_inner">' . do_shortcode($content) . '</div></div>';
   if ($tag === 'et_pb_toggle') return '<div' . $attribute . '><h5 class="et_pb_toggle_title" role="button" tabindex="0" aria-expanded="false">' . esc_html(isset($atts['title']) ? $atts['title'] : 'Pytanie') . '</h5><div class="et_pb_toggle_content" hidden>' . do_shortcode($content) . '</div></div>';
   if ($tag === 'et_pb_social_media_follow') return '<ul' . $attribute . '>' . do_shortcode($content) . '</ul>';
   if ($tag === 'et_pb_social_media_follow_network') return '<li' . $attribute . '><a href="' . esc_url(isset($atts['link']) ? $atts['link'] : 'https://example.invalid/social') . '">' . esc_html($content) . '</a></li>';
   if ($tag === 'trustindex') return '<div' . $attribute . '><article class="ti-review-item">Publiczna opinia testowa 1</article><article class="ti-review-item">Publiczna opinia testowa 2</article><article class="ti-review-item">Publiczna opinia testowa 3</article></div>';
   if ($tag === 'et_pb_contact_form') return '<div' . $attribute . '><form class="company-preserved-form"><label for="company-name">Imię<input id="company-name" name="company-name" required autocomplete="off"></label><label for="company-email">E-mail<input id="company-email" name="company-email" type="email" required autocomplete="off"></label><label for="company-message">Wiadomość<textarea id="company-message" name="company-message" required></textarea></label><button type="submit">Wyślij wiadomość</button><p class="company-form-status" role="status"></p></form></div>';
   return '<div' . $attribute . '>' . do_shortcode($content) . '</div>';
  });
 }
}, 100);
add_action('wp_head', static function () { echo '<style id="company-fixture-css">.yn-company-content .et_pb_section{padding:60px 0}.yn-company-content .et_pb_row{display:flex;gap:32px;width:80%;max-width:1080px;margin:auto}.yn-company-content .et_pb_row:after{content:"";display:block;clear:both}.yn-company-content .et_pb_column{min-width:0;flex:1}.yn-company-content .et_pb_image img{display:block;max-width:100%;height:auto}.yn-company-content .company-old-hero{background:#111;min-height:500px}.yn-company-content .company-old-contact-intro{min-height:260px}.yn-company-content .company-original-cta{background:#ff6600}.yn-company-content .company-original-service p,.yn-company-content .company-original-intro p{font-family:Roboto Condensed!important}.yn-company-content .et_pb_toggle{padding:20px;background:#fff;border:1px solid #ddd}.yn-company-content .et_pb_toggle_title{cursor:pointer;margin:0}.yn-company-content .et_pb_toggle_content{padding-top:12px}.yn-company-content .et_pb_social_media_follow{display:flex;gap:20px;list-style:none}.yn-company-content .company-preserved-form{max-width:700px;display:grid;gap:12px}.yn-company-content .company-preserved-form label{display:grid;gap:4px}.yn-company-content .company-preserved-form input,.yn-company-content .company-preserved-form textarea,.yn-company-content .company-preserved-form button{box-sizing:border-box;max-width:100%;width:100%;min-height:44px;font:inherit;padding:8px}.yn-company-content .trustindex{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.yn-company-content .ti-review-item{min-width:0;padding:16px;background:#fff}@media(max-width:640px){.yn-company-content .trustindex{grid-template-columns:1fr}}</style>'; }, 6);
add_action('wp_footer', static function () { echo '<script id="company-callback-counts" type="application/json">' . wp_json_encode(isset($GLOBALS['company_fixture_callbacks']) ? $GLOBALS['company_fixture_callbacks'] : array()) . '</script><script>document.addEventListener("click",function(e){const title=e.target.closest(".et_pb_toggle_title");if(!title)return;const content=title.nextElementSibling;content.hidden=!content.hidden;title.setAttribute("aria-expanded",String(!content.hidden));});document.addEventListener("submit",function(e){if(e.target.matches(".company-preserved-form")){e.preventDefault();e.target.querySelector(".company-form-status").textContent="TEST: formularz działa";}});</script>'; });
MU;
 if (false === file_put_contents(WPMU_PLUGIN_DIR . '/zzz-company-pages-fixture.php', $mu)) WP_CLI::error('Cannot write the test-only company fixture.');
 require_once WPMU_PLUGIN_DIR . '/zzz-company-pages-fixture.php';
 $fixture = yoona_company_fixture_sources(); $ids = array(); $expected = array();
 foreach ($fixture['sources'] as $path => $body) {
  $record = get_page_by_path(trim($path, '/'), OBJECT, 'page');
  $fields = array('post_type'=>'page','post_status'=>'publish','post_name'=>trim($path,'/'),'post_title'=>$path === '/o-nas/' ? 'O nas' : 'Kontakt','post_content'=>$body);
  if ($record) $fields['ID'] = $record->ID;
  $id = wp_insert_post(wp_slash($fields), true); if (is_wp_error($id)) WP_CLI::error('Cannot prepare public company fixture.');
  update_post_meta($id, '_et_pb_use_builder', 'on'); update_post_meta($id, 'rank_math_title', 'COMPANY NATIVE ' . $fields['post_title']); update_post_meta($id, 'rank_math_description', 'Native company description.'); $ids[$path] = $id;
  preg_match_all('~\[(et_pb_[a-z_]+|trustindex)(?=[\s\]])~', $body, $matches); $counts = array_count_values($matches[1]); ksort($counts); $expected[$path] = $counts;
 }
 update_option('yoona_company_fixture_ids', $ids, false); update_option('yoona_company_expected_callbacks', $expected, false); update_option('yoona_company_fixture_ready', 'public-synthetic-2-v1', false);
 WP_CLI::success('Two public synthetic company sources prepared: 7 photos, 8 FAQs, two office/staff cards, map, reviews, social links and a later manual form. Production hashes and plugin files remain unchanged.'); return;
}
if (!in_array($mode, array('guards','private'), true)) WP_CLI::error('Unknown company probe mode.');
if (!class_exists('Yoona_SEO_Company_Pages')) WP_CLI::error('Company rendering API is unavailable.');
global $post, $wp_query, $wp_filter;
$before = $snapshot(); $checks = 0; $memory_id = 870000;
$check = static function ($condition, $label) use (&$checks) { if (!$condition) WP_CLI::error('Company: ' . $label); ++$checks; };
$seed = get_post((int)get_option('page_on_front'), ARRAY_A);
$set = static function ($path, $body, $changes = array()) use (&$post, &$wp_query, &$memory_id, $seed) {
 ++$memory_id; $row = array_merge($seed, array('ID'=>$memory_id,'post_name'=>trim($path,'/'),'post_type'=>'page','post_status'=>'publish','post_parent'=>0,'post_password'=>'','post_title'=>$path === '/o-nas/' ? 'O nas' : 'Kontakt','post_content'=>$body), $changes);
 $post = new WP_Post((object)$row); wp_cache_set($memory_id, $post, 'posts'); wp_cache_set($memory_id, array('_et_pb_use_builder'=>array('on')), 'post_meta');
 $wp_query = new WP_Query(); $wp_query->is_page=true; $wp_query->is_singular=true; $wp_query->queried_object=$post; $wp_query->queried_object_id=$memory_id; $wp_query->post=$post; $wp_query->posts=array($post); $wp_query->post_count=1; $wp_query->current_post=0; setup_postdata($post); return $memory_id;
};
$render_source = static function () { ob_start(); try { the_content(); return ob_get_contents(); } finally { ob_end_clean(); } };
$property = new ReflectionProperty('Yoona_SEO_Company_Pages', 'data'); $property->setAccessible(true); $prior_data = Yoona_SEO_Company_Pages::data();
try {
 if ($mode === 'private') {
  if (empty($args[1]) || !is_readable($args[1])) WP_CLI::error('CLI-only private diagnostic export required.');
  $property->setValue(null, json_decode(file_get_contents(dirname(YOONA_SEO_FILE) . '/content/company-pages.json'), true));
  $records = array_column(json_decode(file_get_contents($args[1]), true)['pages'], null, 'path');
  $patches = array_column(json_decode(file_get_contents(dirname(YOONA_SEO_FILE) . '/content/changes.json'), true)['pages'], null, 'path'); $versions=0;
  foreach (array('/o-nas/', '/kontakt/') as $path) {
   $source = $records[$path]['post_content']; $prepared = Yoona_SEO_Divi::prepare($source, $patches[$path]['divi_text']); $check(!is_wp_error($prepared), 'frozen SEO counterpart reproduces ' . $path);
   foreach (array($source, $prepared) as $version=>$body) {
    $id=$set($path,$body); $context=Yoona_SEO_Company_Pages::context($id); $check(is_array($context),'real source resolves ' . $path);
    $GLOBALS['company_fixture_callbacks']=array(); $original=$render_source(); $callbacks=$GLOBALS['company_fixture_callbacks'];
    $closing_output=''; $observed=array(); $capture_closing=static function($html,$tag,$atts,$match)use(&$closing_output,&$observed){if($tag==='et_pb_section' && strpos($html,'yn-company-closing')!==false)$closing_output=$html;if(in_array($tag,array('et_pb_section','et_pb_row','et_pb_code'),true))$observed[$tag][]=array(hash('sha256',$match[0]),hash('sha256',unescape_invalid_shortcodes($match[0])),hash('sha256',strtr(unescape_invalid_shortcodes($match[0]),array('&#091;'=>'[','&#093;'=>']'))));return$html;};
    $GLOBALS['company_fixture_callbacks']=array(); add_filter('do_shortcode_tag',$capture_closing,21,4); try{$result=Yoona_SEO_Company_Pages::render($context);}finally{remove_filter('do_shortcode_tag',$capture_closing,21);}
    $check($result['hero_omitted'],'only known real old heading is omitted ' . $path); $check($callbacks===$GLOBALS['company_fixture_callbacks'],'every original callback executes exactly once ' . $path);
    $roles=array_count_values($result['roles']); $expected_roles=$path==='/o-nas/' ? array('legacy-hero'=>1,'intro'=>1,'faq'=>1,'service'=>7,'values'=>1,'narrative'=>2,'closing'=>1,'social'=>1) : array('legacy-intro'=>1,'contact-main'=>1,'office-row'=>1,'map-row'=>1,'contact-code'=>2,'map-code'=>1,'review-code'=>1,'review-link'=>1,'cta'=>1,'reviews'=>1,'social'=>1); ksort($roles);ksort($expected_roles);
    $preflight_roles=array();foreach($context['modules'] as$module_versions)foreach($module_versions as$module)$preflight_roles[]=$module['role'];
    if($roles!==$expected_roles){$pipelines=array('whole-wptexturize'=>wptexturize($body),'whole-autop-unautop'=>shortcode_unautop(wpautop(wptexturize($body))));$diagnostic=array();foreach($pipelines as$name=>$pipeline)foreach(array('et_pb_section','et_pb_row','et_pb_code')as$tag){preg_match_all('/'.get_shortcode_regex(array($tag)).'/s',$pipeline,$raw_modules,PREG_SET_ORDER);$matches=0;foreach($raw_modules as$i=>$raw_module)if(isset($observed[$tag][$i]) && in_array(hash('sha256',$raw_module[0]),$observed[$tag][$i],true))++$matches;$diagnostic[$name][$tag]=array('expected'=>count($raw_modules),'observed'=>count(isset($observed[$tag])?$observed[$tag]:array()),'exact_or_unescaped_matches'=>$matches);}WP_CLI::log('Known-source render pipeline counts: '.wp_json_encode($diagnostic));}
    $check($roles===$expected_roles,'every reviewed original module receives its correct role exactly once ' . $path . ' variant=' . $version . ' counts=' . wp_json_encode($roles) . ' preflight=' . wp_json_encode(array_count_values($preflight_roles)));
    if($path==='/o-nas/') { $check(substr_count($result['html'],'<img')===7,'all seven actual original photos survive'); $check(substr_count($result['html'],'et_pb_toggle_title')===8,'all eight actual original FAQs survive'); $check($closing_output!=='' && !preg_match('~<h1(?:\\s|>)~i',$closing_output) && ($version!==0 || preg_match('~<h2(?:\\s|>)~i',$closing_output)),'the reviewed closing H1 becomes H2 while any existing SEO introduction H1 is retained (capture_bytes=' . strlen($closing_output) . ',h1=' . preg_match_all('~<h1(?:\\s|>)~i',$closing_output) . ',h2=' . preg_match_all('~<h2(?:\\s|>)~i',$closing_output) . ')'); }
    $check(substr_count($result['html'],'<iframe')===substr_count($original,'<iframe'),'all original map iframe roots retained ' . $path);
    $check(substr_count($result['html'],'class="ti-review-item"')===substr_count($original,'class="ti-review-item"'),'original review callback output retained ' . $path);
    $check(substr_count($result['html'],'et_pb_toggle_title')===substr_count($original,'et_pb_toggle_title'),'original FAQ callback output retained ' . $path);
    $check(substr_count($result['html'],'<img')===substr_count($original,'<img'),'all original image roots retained ' . $path);
    foreach (array('tel:', 'mailto:', 'facebook.com', 'instagram.com') as $token) $check(substr_count($result['html'],$token)===substr_count($original,$token),'original ' . $token . ' targets retained ' . $path);
    ++$versions; wp_cache_delete($id,'posts'); wp_cache_delete($id,'post_meta');
   }
  }
  $check($versions===4,'both original and SEO variants of both company routes covered');
 } else {
  if (!function_exists('yoona_company_fixture_sources')) WP_CLI::error('Prepare the public company fixture first.');
  $fixture=yoona_company_fixture_sources();
  foreach ($fixture['sources'] as $path=>$body) {
   $id=$set($path,$body); $context=Yoona_SEO_Company_Pages::context($id); $check(is_array($context),'known synthetic source resolves ' . $path);
   $GLOBALS['company_fixture_callbacks']=array(); $original=$render_source(); $callbacks=$GLOBALS['company_fixture_callbacks'];
   $GLOBALS['company_fixture_callbacks']=array(); $result=Yoona_SEO_Company_Pages::render($context);
   $check($result['hero_omitted'] && $GLOBALS['company_fixture_callbacks']===$callbacks,'known heading omitted after all original callbacks execute once ' . $path);
   $check(strpos($result['html'],'company-manual-form-section')!==false || $path==='/o-nas/','later manual native form is preserved ' . $path);
   // Change every original annotated/omitted module. Unknown source must retain
   // its complete content and must not receive any reviewed role annotation.
   $changed=preg_replace('~\[(et_pb_[a-z_]+)(?=[\s\]])~', '[$1 synthetic_edit="yes"', $body); $id=$set($path,$changed);
   $context=Yoona_SEO_Company_Pages::context($id); $GLOBALS['company_fixture_callbacks']=array(); $expected=$render_source(); $GLOBALS['company_fixture_callbacks']=array(); $result=Yoona_SEO_Company_Pages::render($context);
   $check(!$result['hero_omitted'] && $result['html']===$expected,'all manually edited sections fall back to exact complete source output ' . $path);
   foreach (array('post_password'=>'private','post_status'=>'draft') as $field=>$value) { $id=$set($path,$body,array($field=>$value)); $check(Yoona_SEO_Company_Pages::context($id)===false,'protected/nonpublic source declines company context ' . $path); }
   $id=$set($path,$body); foreach (array('et_fb','et_bfb','et_pb_preview','elementor-preview') as $key) { $_GET[$key]='1'; try { $check(Yoona_SEO_Company_Pages::context($id)===false,'builder route declines company context ' . $key); } finally { unset($_GET[$key]); } }
  }
  $id=$set('/banery/','<p>Unrelated offer.</p>'); $check(Yoona_SEO_Company_Pages::context($id)===false,'unrelated offer stays outside company renderer');
  $id=$set('/o-nas/',$fixture['sources']['/o-nas/']); $context=Yoona_SEO_Company_Pages::context($id);
  $throw=static function ($html,$tag) { if ($tag==='et_pb_text') throw new RuntimeException('synthetic-company-render-exception'); return $html; };
  add_filter('do_shortcode_tag',$throw,19,2); $prior_hooks=$wp_filter['do_shortcode_tag']->callbacks; $prior_buffers=ob_get_level(); $caught=false;
  try { try { Yoona_SEO_Company_Pages::render($context); } catch (RuntimeException $error) { $caught=$error->getMessage()==='synthetic-company-render-exception'; } $check($caught,'shortcode exception propagates'); $check($wp_filter['do_shortcode_tag']->callbacks===$prior_hooks,'temporary annotation filter removed after exception and prior hooks unchanged'); $check(ob_get_level()===$prior_buffers,'output buffer depth restored after exception'); } finally { remove_filter('do_shortcode_tag',$throw,19); }
  $result=Yoona_SEO_Company_Pages::render($context); $check($result['hero_omitted'],'next render succeeds after exceptional render');
 }
 $check($snapshot()===$before,'all CLI source checks remained in memory and every database post/meta row remained byte-identical');
 WP_CLI::success($checks . ' company ' . $mode . ' assertions passed. No private source HTML was printed or installed.');
} finally { $property->setValue(null,$prior_data); if (isset($id)) {wp_cache_delete($id,'posts'); wp_cache_delete($id,'post_meta');} }
