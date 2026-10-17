<?php
defined( 'ABSPATH' ) || exit;
$yn_data = Yoona_SEO_Design::data();
$yn_home_services = Yoona_SEO_Design::offer_cards();
Yoona_SEO_Design::part( 'header' );
?>
<main id="main-content" class="yn-main">
	<section class="yn-hero" aria-labelledby="yn-hero-title">
		<div class="yn-hero-media"><?php echo Yoona_SEO_Design::image( $yn_data['hero'], 'yn-hero-image', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<div class="yn-container yn-hero-content">
			<div class="yn-hero-copy">
				<p class="yn-eyebrow">Reklama wielkoformatowa / identyfikacja / produkcja</p>
				<h1 id="yn-hero-title">Robimy<br>marki<br><span>widoczne.</span></h1>
				<p class="yn-hero-description">Projektujemy, produkujemy i montujemy reklamę, która działa w realnym świecie. Od pomysłu do efektu&nbsp;WOW.</p>
				<div class="yn-actions"><a class="yn-button" href="#oferta">Zobacz, co robimy <span aria-hidden="true">↗</span></a><a class="yn-button yn-button-outline" href="<?php echo esc_url( Yoona_SEO_Design::route( 'contact' ) ); ?>">Porozmawiajmy</a></div>
			</div>
			<span class="yn-hero-caption">YOONA / REKLAMA, KTÓRĄ WIDAĆ</span>
		</div>
	</section>
	<div class="yn-capabilities" aria-label="Od pomysłu do montażu">
		<div class="yn-container yn-capability-grid">
			<div><strong>Projekt</strong><span>Pomysł nabiera kształtu</span></div>
			<div><strong>Druk</strong><span>Kolor, format, detal</span></div>
			<div><strong>Produkcja</strong><span>Własne wykonanie</span></div>
			<div><strong>Montaż</strong><span>Efekt w Twojej przestrzeni</span></div>
		</div>
	</div>
	<section id="oferta" class="yn-section yn-services" aria-labelledby="yn-services-title">
		<div class="yn-container">
			<div class="yn-section-heading">
				<div><p class="yn-eyebrow">Nasze możliwości</p><h2 id="yn-services-title">Od pomysłu<br>do <span>efektu WOW.</span></h2></div>
				<p>Agencja reklamowa YOONA — Olsztyn i Ostróda. Projektujemy, drukujemy, produkujemy i montujemy reklamę dla firm, gastronomii i klientów indywidualnych.</p>
			</div>
			<div class="yn-service-grid">
				<?php foreach ( array_slice( $yn_home_services, 0, 8 ) as $yn_service ) : ?>
					<a class="yn-service-card" href="<?php echo esc_url( $yn_service['url'] ); ?>">
						<div class="yn-service-image"><?php echo Yoona_SEO_Design::image( $yn_service ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
						<h3><?php echo esc_html( $yn_service['name'] ); ?><span aria-hidden="true">↗</span></h3>
					</a>
				<?php endforeach; ?>
			</div>
			<?php if ( count( $yn_home_services ) > 8 ) : ?>
				<div class="yn-home-categories" aria-labelledby="yn-home-categories-title">
					<h3 id="yn-home-categories-title">Wszystkie możliwości dla Twojej marki</h3>
					<ul class="yn-category-grid">
						<?php foreach ( array_slice( $yn_home_services, 8 ) as $yn_service ) : ?>
							<li><a class="yn-category-link" href="<?php echo esc_url( $yn_service['url'] ); ?>"><?php echo esc_html( $yn_service['name'] ); ?><span aria-hidden="true">↗</span></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
			<a class="yn-text-link yn-all-services" href="<?php echo esc_url( Yoona_SEO_Design::route( 'services' ) ); ?>">Poznaj pełną ofertę <span aria-hidden="true">↗</span></a>
		</div>
	</section>
	<section id="proces" class="yn-section yn-process" aria-labelledby="yn-process-title">
		<div class="yn-container">
			<div class="yn-section-heading">
				<div><p class="yn-eyebrow">Jak działamy</p><h2 id="yn-process-title">Jeden zespół.<br><span>Cały proces.</span></h2></div>
				<p>Od pierwszej rozmowy, przez produkcję, aż po montaż — wszystko w jednych rękach. Dzięki temu razem pilnujemy jakości, spójności i&nbsp;terminu realizacji.</p>
			</div>
			<ol class="yn-process-grid">
				<li><span class="yn-process-number">01</span><h3>Rozmawiamy</h3><p>Poznajemy Twoje cele, potrzeby i miejsce realizacji.</p></li>
				<li><span class="yn-process-number">02</span><h3>Projektujemy</h3><p>Tworzymy koncepcję, która pasuje do Twojej marki.</p></li>
				<li><span class="yn-process-number">03</span><h3>Produkujemy</h3><p>W naszym zapleczu projekt zamienia się w gotową reklamę.</p></li>
				<li><span class="yn-process-number">04</span><h3>Montujemy</h3><p>Docieramy na miejsce i dbamy o prawidłowe wykonanie montażu.</p></li>
				<li><span class="yn-process-number">05</span><h3>Robimy efekt</h3><p>Twoja marka zyskuje widoczność w realnej przestrzeni.</p></li>
			</ol>
		</div>
	</section>
	<section id="realizacje" class="yn-section yn-projects" aria-labelledby="yn-projects-title">
		<div class="yn-container">
			<div class="yn-section-heading yn-projects-heading">
				<div><p class="yn-eyebrow">Nasze realizacje</p><h2 id="yn-projects-title">Nie opowiadamy.<br><span>Pokazujemy.</span></h2></div>
				<p>Wybrane realizacje reklamy zewnętrznej, świetlnej, brandingu lokali i produktów reklamowych.</p>
				<a class="yn-button yn-button-light" href="<?php echo esc_url( Yoona_SEO_Design::route( 'projects' ) ); ?>">Zobacz wszystkie<br>realizacje <span aria-hidden="true">↗</span></a>
			</div>
			<div class="yn-project-grid">
				<?php foreach ( $yn_data['projects'] as $yn_project ) : ?>
					<a class="yn-project-card" href="<?php echo esc_url( Yoona_SEO_Design::url( $yn_project['url'] ) ); ?>">
						<?php echo Yoona_SEO_Design::image( $yn_project ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span class="yn-project-name"><?php echo esc_html( $yn_project['name'] ); ?><span aria-hidden="true">↗</span></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<section class="yn-orange-band" aria-labelledby="yn-agency-title">
		<div class="yn-container yn-orange-grid">
			<div><p class="yn-eyebrow">Cała reklama. Jeden adres.</p><h2 id="yn-agency-title">Agencja?<br>Nie tylko.</h2></div>
			<p>Tworzymy reklamę nie tylko na ekranie. Łączymy pomysł, projekt i własną produkcję. Realizujemy to, co wspólnie zaplanujemy.</p>
			<a class="yn-button yn-button-orange-outline" href="<?php echo esc_url( Yoona_SEO_Design::route( 'about' ) ); ?>">Poznaj nas <span aria-hidden="true">↗</span></a>
		</div>
	</section>
	<section class="yn-section yn-about" aria-labelledby="yn-about-title">
		<div class="yn-container yn-about-grid">
			<div><p class="yn-eyebrow">YOONA Creative Group</p><h2 id="yn-about-title">Agencja<br>reklamowa.<br><span>Olsztyn<br>i Ostróda.</span></h2></div>
			<div class="yn-about-copy"><p>Reklama w Olsztynie i Ostródzie — od projektu do montażu. Wykonujemy reklamę świetlną, kasetony reklamowe, litery 3D, tablice, ledony i neony.</p><p>Druk wielkoformatowy i banery reklamowe łączymy z oklejaniem samochodów i szyb. Oferujemy też zmianę koloru samochodu, beczki reklamowe, markizy oraz frezowanie CNC.</p><p>Dobieramy rozwiązanie do miejsca, sposobu użytkowania i budżetu. Obsługujemy firmy z Warmii i Mazur oraz realizujemy zlecenia w całej Polsce.</p><a class="yn-button" href="<?php echo esc_url( Yoona_SEO_Design::route( 'about' ) ); ?>">Poznaj YOONA <span aria-hidden="true">↗</span></a></div>
			<div class="yn-about-image"><?php echo Yoona_SEO_Design::image( $yn_data['about'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span class="yn-image-label">Od projektu do montażu.</span></div>
		</div>
	</section>
</main>
<?php Yoona_SEO_Design::part( 'footer' ); ?>
