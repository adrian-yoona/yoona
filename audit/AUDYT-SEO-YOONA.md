# Audyt SEO i treści yoona.pl

Najpilniejsza poprawka to dopasowanie tytułów i opisów do tego, co użytkownik rzeczywiście znajdzie na stronie. Eksport pokazuje masowo stosowane obietnice typu „5 powodów” i „10 najlepszych”, sztuczne powtarzanie fraz oraz teksty skopiowane z innych usług.

Źródło: eksport WordPress z 2026-10-15T12:32:52+00:00; przeanalizowano wszystkie 200 elementów: 47 stron i 153 wpisów. Eksport oznaczono jako nieucięty: True. Właściciel potwierdził korzystanie z Rank Math.

To audyt danych i treści z eksportu, nie pomiar działającej witryny. Ograniczenie dostępu do strony w środowisku uniemożliwiło kontrolę odpowiedzi HTTP i wynikowego HTML. Nie przypisujemy tej blokady witrynie.

## Przygotowane poprawki do wgrania

Gotowy pakiet treści, dołączony również do wersji 3 wtyczki, obejmuje **tytuły i opisy Rank Math dla wszystkich 200 elementów oraz 896 poprawek tekstów w 56 elementach** (45 stron i 11 realizacji). Z tego 716 zmian dotyczy modułów tekstowych, a 180 odpowiedzi FAQ. Metadane są zapisywane w natywnych polach Rank Math; teksty we wnętrzach istniejących modułów Divi. Wersja 3 dodaje osobną [przebudowę wyglądu](../docs/ZMIANY-WYGLADU.md) według obrazu właściciela.

Poprawiono m.in. błędne nazwy usług i klientów, instrukcję redakcyjną na menuboardach, nagłówki o neonach na CNC, nieuzasadnione obietnice, naturalność fraz oraz opisy usług i procesu zamówienia. Pozostałości kodu interfejsu czatu usunięto z poprawianych modułów. Zachowano adresy URL, strukturę shortcode, media i formularze. Treść polityki prywatności i strony listy realizacji nie jest zmieniana.

[Wtyczka do pobrania](../downloads/yoona-seo-v3.5.0.zip) · [Instrukcja wdrożenia](../README.md) · [Dokładny zakres adresów](zakres-poprawek.json) · [Wyniki testów](../docs/WYNIKI-TESTOW.md).

Pakiet nie został zdalnie wdrożony na yoona.pl. Właściciel aktualizuje wtyczkę, sprawdza podgląd i uruchamia **Wgraj zmiany na zaznaczone strony**. Pomiary i kontrole opisane w P3 pozostają do wykonania na działającej witrynie; nie zastąpiono ich analizą eksportu.

## Co potwierdzono w danych

| Zapisane pole / obserwacja | Wszystkie | Strony | Wpisy |
| --- | ---: | ---: | ---: |
| Liczba elementów | 200 | 47 | 153 |
| Własny title Rank Math zapisany w eksporcie | 80 | 43 | 37 |
| Brak zapisanego własnego title Rank Math | 120 | 4 | 116 |
| Własny opis Rank Math zapisany w eksporcie | 80 | 43 | 37 |
| Brak zapisanego własnego opisu Rank Math | 120 | 4 | 116 |
| Zapisany title z obietnicą liczbową | 37 | 37 | 0 |
| Brak H1 w samym zapisanym korpusie | 140 | 12 | 128 |
| Więcej niż jeden H1 w zapisanym korpusie | 23 | 20 | 3 |
| Brak linku wewnętrznego w zapisanym korpusie | 124 | 2 | 122 |

Brak zapisanego własnego title/opisu nie dowodzi braku tagu na stronie: Rank Math może używać szablonu. Nagłówki i linki wyżej dotyczą samego `post_content`; nie obejmują nagłówka strony, menu, stopki ani dynamicznych modułów. Nie nazywamy tych wpisów osieroconymi bez crawla.

Znacznik buildera Divi występuje w 66 elementach, a shortcode et_pb w 65. Zapisane tytuły Rank Math: 0 grup ścisłych duplikatów; opisy: 0 grup. Porównanie ignoruje wielkość liter i wielokrotne spacje, nie porównuje podobieństwa znaczeniowego.

## P1 — poprawić w pierwszej kolejności

### 1. Usunąć obietnice list i dopasować metadane do intencji

W 37 zapisanych title pojawiają się wzorce „N powodów / najlepszych / pomysłów / sposobów”. W 19 z nich w zapisanym korpusie nie znaleziono ani ponumerowanych nagłówków, ani elementów listy li. To sygnał do przeglądu, nie dowód spamu ani kary Google; listy mogą też znajdować się w modułach dynamicznych lub zwykłych akapitach. Dla stron ofertowych bez takiej listy lepszy jest konkretny tytuł usługi, lokalizacja i marka.

- **https://yoona.pl/kontakt/** — Tytuł kontaktu obiecuje 5 powodów współpracy, podczas gdy zapisany korpus jest stroną kontaktową. Ustawić opisowy tytuł kontaktu i zaproszenie do przesłania danych potrzebnych do wyceny.
  - Zapisany title: „Yoona kontakt: 5 powodów nawiązania współpracy”.
- **https://yoona.pl/tablice-unijne/** — Opis tablic unijnych mówi o wzmocnieniu marki. Treść strony opisuje obowiązki informacyjne projektów dofinansowanych i wymagania programu. Opisać wykonanie tablic według dokumentacji konkretnego projektu; bez obietnicy uniwersalnej zgodności prawnej.
  - Zapisany title: „Tablice unijne Olsztyn: 5 sposobów na ich wykorzystanie”.
- **https://yoona.pl/frezowanie-cnc/** — W źródłowej treści strony CNC występują nagłówki dotyczące neonów; to widoczny ślad kopiowania szablonu. Zastąpić te nagłówki tematem CNC w tych samych modułach, zachowując strukturę Divi.
  - Zapisany title: „Frezowanie CNC Olsztyn i Ostróda: 5 powodów, by wybrać nas”.
- **https://yoona.pl/reklama-na-rusztowaniu/** — Zapisany title i description zawierają literówkę „rekalmy”. Usunąć literówkę i obietnicę liczbową; nazwać reklamę na rusztowaniach.
  - Zapisany title: „Reklama na rusztowaniu rekalmy na rusztowaniach – 10 powodów, by wybrać!”.
- **https://yoona.pl/litery-3d/** — Title i description powtarzają dokładną frazę „litery 3d olsztyn” w nienaturalnej odmianie. Zastosować poprawną polszczyznę i jedną, naturalną lokalizację usługi.
  - Zapisany title: „Litery 3D Olsztyn – produkcja i montaż liter 3d olsztyn”.
- **https://yoona.pl/oklejanie-samochodu-cena/** — Strona obiecuje informację o cenie oklejania; nie ma podstaw do dodawania zmyślonego cennika. Wyjaśnić czynniki wyceny i dane potrzebne do zapytania; cenę podać dopiero po potwierdzeniu przez firmę.
  - Zapisany title: „[nie zapisano]”.

### 2. Naprawić język i pozostałości szablonu w głównych usługach

Na stronie liter 3D zapisano: „Litery 3D Olsztyn – produkcja i montaż liter 3d olsztyn”. W opisie tablic występuje końcówka „tablice reklamowe olsztyn szyldy”, a w opisie autobusów „z oklejanie autobusów olsztyn”. To konkretne błędy naturalności języka. Zalecenie: pisać o produkcie, zakresie prac i kolejnym kroku klienta, bez doklejania nieodmienionej frazy.

Na `/frezowanie-cnc/` należy przejrzeć wszystkie podpisy odziedziczone po neonach. Na `/tablice-unijne/` opisać przeznaczenie tablic informacyjnych oraz pracę według wytycznych beneficjenta. Nie zastępować tych informacji ogólnym tekstem o budowaniu marki.

### Pozostałości interfejsu czatu w treści

W części modułów zapisano obcy kod interfejsu: klasy `react-scroll-to-bottom`, atrybuty `data-message-author-role` i puste znaczniki formularza. Nie są potrzebne do wyświetlenia opisu usługi. W gotowych poprawkach usunięto te pozostałości w 13 modułach ośmiu stron, m.in. pylonów, mobilnej reklamy, CNC i tablic unijnych, zachowując właściwy tekst i linki. Formularze witryny w pozostałych modułach pozostają nietknięte.

### 3. Zachować układ Divi i sprawdzić wdrożenie na kopii

Teksty edytować w konkretnych modułach. Zastąpienie całego post_content zwykłym HTML usunęłoby konfigurację układu, galerie i przyciski. Najpierw kopia treści/metadanych, podgląd zmian, potem zapis. Nie zmieniać adresów URL automatycznie, np. `/reklama-na-rusztowaniu-2/`, bez planu przekierowań.

## P2 — dopracować strukturę i portfolio

### 4. Uporządkować własne tytuły i opisy, zwłaszcza realizacji

116 z 153 realizacji nie ma własnego title Rank Math w eksporcie; tyle samo nie ma własnego opisu. Najpierw sprawdzić fallback aktualnego HTML. Przygotować konkretne metadane o wykonanej realizacji, kliencie i miejscowości na podstawie treści. Nie dopisywać technologii, parametrów ani wyników sprzedażowych, których źródło nie potwierdza.

### 5. Dodać użyteczne przejścia z realizacji do usługi

W 122 zapisanych korpusach wpisów brak wewnętrznego linku. To okazja do ręcznego powiązania realizacji z odpowiednią usługą, np. oklejaniem samochodów, literami 3D lub kasetonami. Menu i motyw mogą już zapewniać część tych połączeń. Linków spoza eksportu nie oznaczono jako uszkodzone: nie wykonano pomiaru HTTP.

### 6. Ograniczyć powtarzalny tekst i opisać proces

Zamiast kolejnych ogólnych zdań o „wysokiej jakości” podać informacje przydatne przy zamówieniu: zastosowanie, możliwe materiały, przygotowanie plików, zakres montażu, sposób wyceny. W realizacjach opisać faktycznie wykonany zakres. Nie ma uniwersalnego wymogu minimalnej liczby słów.

- Ten sam akapit występuje w 34 korpusach, np. https://yoona.pl/: „nasz zespół jest gotowy odpowiedzieć na twoje zapytania od poniedziałku do piątku w godzinach 7:30-15:30. jeśli masz jakiekolwiek pytania dotyczące naszych usług, strategii marketingowych, projektów kreatywnych lub chcesz umówić się na spotkanie, nie wahaj się…”. Powtarzalna stopka lub CTA nie jest sama w sobie błędem; nie powinna zastępować opisu usługi.

### Nagłówki: uprościć hierarchię i usunąć puste elementy

W 23 zapisanych korpusach występuje więcej niż jeden H1, w tym w 20 stronach. Na stronie głównej zapisano H1 „— agencja reklamowa olsztyn | ostróda” oraz drugi H1 „Agencja reklamowa Olsztyn – Producent reklam dla firm”. Warto pozostawić opisowy nagłówek główny, a etykietę obszaru działania nadać zwykłym tekstem. Na `/frezowanie-cnc/` i `/tablice-unijne/` występują też puste H1. Sama liczba H1 nie jest podstawą do twierdzenia o karze Google; celem jest czytelny dokument i dostępność. Po zmianie sprawdzić DOM wygenerowany przez Divi oraz wygląd tekstu na telefonie.


## P3 — kontrola techniczna po uzyskaniu dostępu

### 7. Sprawdzić finalny HTML, indeksację i wyniki

Po wdrożeniu sprawdzić w przeglądarce title/opis/canonical/robots, strukturę nagłówków wygenerowaną przez Divi i mobile. Zweryfikować robots.txt, mapę XML w Search Console, przekierowania i linki. Dopiero wtedy zmierzyć Lighthouse/Core Web Vitals i ustalić działania wydajnościowe. Skuteczność treści oceniać w Search Console i zapytaniach klientów; ten eksport nie zawiera danych o ruchu ani pozycjach.

### 8. Ustawienia — co wiemy i czego nie zmieniać automatycznie

- `blog_public`: `1` — WordPress w chwili eksportu nie miał włączonej globalnej prośby o nieindeksowanie. To nie potwierdza indeksacji Google ani finalnych nagłówków robots.
- Linki bezpośrednie: `/%postname%/`; strona główna ma ID `8`, strona wpisów ID `1553`. Strona wpisów może korzystać z szablonu archiwum zamiast własnego korpusu.
- Obecność starych pól Yoast oraz nowych pól Rank Math w bazie nie dowodzi jednoczesnego działania obu wtyczek. Właściciel wskazał Rank Math; nie usuwać dawnych pól bez osobnej decyzji.
- Nie dodawać globalnego canonical, noindex ani drugiego generatora schema bez przeglądu aktualnego HTML i ustawień Rank Math.

## Ograniczenia i niewykonane pomiary

- Nie sprawdzono końcowych tagów title, meta description, canonical i robots w odpowiedzi HTML; brak pola w bazie może korzystać z szablonu Rank Math.
- Nie sprawdzono kodów HTTP, przekierowań, robots.txt, map XML, statusu indeksacji i poprawności końcowego schema/hreflang/OG.
- Nie zmierzono Core Web Vitals, Lighthouse, czasu ładowania, rozmiaru zasobów ani działania mobilnego; Divi i rozmiar eksportu nie są pomiarem szybkości.
- Nie uzyskano Google Search Console ani Analytics; brak danych o pozycjach, kliknięciach, wyświetleniach, CTR, ruchu i konwersji.
- H1/H2 i linki dotyczą tylko zapisanej treści; motyw/Divi może dodać nagłówki, menu, stopkę i linki. Brak linku w post_content nie dowodzi osierocenia strony.
- Nie oceniono alt obrazów generowanych przez media WordPress/Divi. Wyeksportowane identyfikatory galerii nie zawierają opisów mediów.
- Tekst shortcode modułów dynamicznych/kodu jest wyłączony z liczby słów; nie uruchamiano PHP, shortcode, HTML ani JavaScript.
- Nie wykonywano oceny prawnej dokumentów ani weryfikacji cen, terminów i danych kontaktowych; raport pomija telefony i e-maile.

## Artefakty i kontrola jakości

`export-audit.json` zawiera wyniki dla wszystkich 200 elementów, policzone pola, nagłówki zapisanej treści i listę linków bez pomiaru HTTP. Raport celowo nie zawiera surowego eksportu, całych tekstów, telefonów ani adresów e-mail. Surowy eksport należy przechowywać prywatnie.

Po imporcie: otworzyć stronę główną, Kontakt, ofertę i 2–3 realizacje na komputerze i telefonie; sprawdzić Divi, przyciski, formularze, galerie i faktyczne metadane. W razie błędu przywrócić zapisane kopie. Żadnego wzrostu pozycji ani ruchu nie można zagwarantować na podstawie eksportu.
