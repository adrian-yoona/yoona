=== Yoona SEO — audyt i wdrożenie treści ===
Contributors: yoona
Tags: seo, content, backup
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Podgląd zmian SEO, kontrolowane wdrożenie i cofnięcie. Eksport treści do audytu.

== Description ==

Wtyczka umożliwia import przygotowanego pakietu JSON, porównanie obecnych i proponowanych wartości, wybór stron i wdrożenie zmian. Zachowuje kopię poprzednich wartości. Cofnięcie jest blokowane, gdy zmieniane pola zostały później zmodyfikowane.

Sama aktywacja nie zmienia witryny. Pusty pakiet lub pakiet z niezakończonym audytem nie może zostać zastosowany. W tej wersji dołączony pakiet pozostaje pusty, ponieważ nie udało się odczytać yoona.pl przez filtr sieciowy środowiska przygotowującego audyt. Nie zawiera jeszcze poprawionych tekstów.

W Narzędzia → Yoona SEO można pobrać diagnostykę do dalszego audytu. Eksport zawiera opublikowane treści i wybrane ustawienia witryny. Nie zawiera kont użytkowników ani hurtowego zrzutu ustawień. Traktuj pobrany plik jak kopię zawartości swojej strony i przekazuj go wyłącznie zaufanej osobie.

Wtyczka nie wysyła danych do zewnętrznych usług i nie wymaga klucza API.

Metadane SEO są obsługiwane przez filtry WordPressa, Yoast SEO, Rank Math, All in One SEO i SEOPress. Inne integracje wymagają osobnego sprawdzenia. Wtyczka musi pozostać aktywna, aby jej metadane były emitowane.

Zmiany całej treści wymagają zgodnego odcisku SHA-256 poprzedniej treści. Kreatory takie jak Elementor, Divi i WPBakery nie są obsługiwane do nadpisywania całej treści. Wtyczka nie zmienia adresów URL, canonical ani ustawień indeksowania i nie tworzy nowych stron.

== Installation ==

1. Wykonaj pełną kopię witryny w panelu hostingu.
2. W panelu WordPressa otwórz Wtyczki → Dodaj nową → Wyślij wtyczkę na serwer.
3. Wgraj plik yoona-seo.zip i aktywuj wtyczkę.
4. Przejdź do Narzędzia → Yoona SEO.
5. Pobierz diagnostykę do przygotowania audytu lub wczytaj gotowy pakiet zmian, jeśli już go masz.
6. Sprawdź podgląd i zaznacz strony. Dopiero przycisk wdrożenia zapisuje zmiany.
7. Wyczyść cache witryny i sprawdź wynik. Do cofnięcia użyj historii w tym samym panelu.

== Frequently Asked Questions ==

= Czy aktywacja nadpisze moją stronę? =
Nie. Do zapisu potrzebna jest poprawna paczka zmian, zakończony audyt i wybór stron przez administratora.

= Czy wyłączenie wtyczki cofnie zmiany? =
Nie. Wyłączenie zatrzymuje jej filtry metadanych. Zmiany treści pozostają w WordPressie. Cofnij je w historii wdrożeń przed wyłączeniem, jeśli chcesz przywrócić poprzedni stan.

= Czy kopia we wtyczce zastępuje backup hostingu? =
Nie. Obejmuje wyłącznie zmieniane pola i własne metadane wtyczki.

= Czy wtyczka gwarantuje pozycję w Google? =
Nie. Ranking i sposób prezentacji wyniku zależą również od Google i innych czynników.

== Changelog ==

= 1.0.0 =
* Podgląd pakietu, wdrożenie wybranych stron, kopia zmian i cofnięcie z kontrolą konfliktów.
* Eksport danych do audytu i integracje metadanych SEO.
