# Analiza dostarczonych eksportów — zakres i metodologia

Stan sprawdzenia: 17 października 2026. Szczegółowe wyniki liczbowe są w prywatnym
raporcie lokalnym; nie dołączono ich ani surowych eksportów do publicznej paczki.
Nie wykonano żadnej zmiany na kontach Google.

Późniejsze potwierdzenie użytkownika i zrzut WordPressa wskazują Site Kit
połączony z Analytics, CookieYes oraz aktywne LiteSpeed Cache i Imagify.
To podstawa integracji pomiaru; obecność GTM nie została potwierdzona. Nie jest
to dowód poprawnej konfiguracji tagów, zgód, zdarzeń kluczowych i szybkości.

## Jakie dane faktycznie otrzymano

| Źródło | Zakres możliwy do ustalenia | Co można ocenić | Czego plik nie potwierdza |
|---|---|---|---|
| Search Console, ZIP skuteczności | Wiersze wykresu: 15.07–14.10.2026, wyszukiwanie tekstowe | Kliknięcia, wyświetlenia, CTR, średnie pozycje, zapytania, URL i urządzenia | Stan indeksacji, canonical wybrany przez Google, poprawność mapy, pozycje konkurentów |
| GA4, „Przegląd raportów.csv” | Metadane sekcji: 01.01–17.10.2026 | Użytkownicy, zdarzenia, tytuły stron, pozyskanie użytkownika i sesji, miasta | Zweryfikowane leady, definicje zdarzeń kluczowych, hostname, stan zgód i konfiguracji tagów |
| „brand_performance_report.zip” | Etykiety tygodniowych koszyków częstotliwości od września do października | Unikalny zasięg i średnia częstotliwość, przekroje urządzeń i kraju | Koszty, kliknięcia, zapytania, nazwy kampanii, konwersje, przychód i pełny zakres dat kampanii |

Nazwa pliku Search Console zawiera datę eksportu, ale ostatni wiersz danych
pochodzi z wcześniejszego dnia. Nie wolno użyć tego eksportu jako oceny skutków
aktualizacji strony z 15–17 października.

## Zasady obliczeń

- Łączny CTR = suma kliknięć / suma wyświetleń, a nie średnia procentów z wierszy.
- Średnia pozycja = suma (pozycja × wyświetlenia) / suma wyświetleń. Wartość jest
  przybliżona, ponieważ pozycje wejściowe są zaokrąglone.
- Wykres dzienny jest punktem odniesienia dla sum całej usługi. Tabeli URL nie
  dodaje się do wykresu: jeden wynik wyszukiwania może pokazać kilka URL strony.
- Tabela zapytań jest niepełna, m.in. ze względu na anonimizację. Nieobecnych
  zapytań nie wolno przypisać do ruchu niemarkowego.
- Podział według tokenu „yoona” to grupowanie tekstu. Sama nazwa może również
  dotyczyć osoby; warianty pisowni pozostają osobną grupą niejednoznaczną.
- Ostatnie 28 pełnych dni eksportu porównuje się z poprzednimi 28 dniami.
  Zmiana pozycji lub CTR nie dowodzi przyczyny ani nie jest prognozą wzrostu.
- Oddzielne tabele zapytań i URL nie umożliwiają diagnozy kanibalizacji. Do tego
  potrzebny jest zestaw zapytanie × strona przy jednakowych filtrach.

## Ustalone kierunki pracy

Istniejące oferty usług pojawiają się przy lokalnych zapytaniach, a część
widocznych ofert zdobywa niewiele wejść wobec liczby wyświetleń. Priorytetem jest
spójne dopasowanie treści, tytułu i intencji, a następnie ocena pozycji oraz
konkurencji. Samo dopisanie miasta lub rozbudowanie każdego tekstu nie rozwiązuje
tego problemu. Wybrano dziesięć faktycznie występujących zapytań; mapa usług
i kolejność są w [planie SEO](PLAN-SEO-LOKALNE.md).

Nie wyciągamy wniosku, że desktop ma gorszy wygląd, tylko dlatego, że ma niższy
CTR. Mobilne i komputerowe wyniki mają inne średnie pozycje i mogą obejmować
inny zestaw zapytań. Nie zakładamy również, że miasto użytkownika w GA4 jest
miastem przyszłego zlecenia.

W GA4 widać podejrzany tytuł strony i źródło referral `trafficheap.cc`, co wymaga
weryfikacji jakości pomiaru. Taki zapis nie dowodzi włamania ani zmiany treści
w witrynie. Należy sprawdzić hostname, page_location, stream i zdarzenia w tej
grupie ruchu; samo dopisanie domeny do niechcianych odesłań nie usuwa spamu
i nie naprawia automatycznie danych historycznych.

Raport zawiera zbiorcze „najważniejsze wydarzenia” bez ich nazw i definicji.
Nie traktujemy tego wskaźnika jako liczby zapytań ofertowych. Niskiego
współczynnika odrzuceń nie uznajemy automatycznie za dowód dobrej jakości ruchu.
Pierwsze źródło użytkownika i źródło sesji to różne zakresy pomiaru; liczby
użytkowników w wierszach różnych wymiarów nie zawsze się sumują.

Widok kanałów w Site Kit jest prezentacją danych połączonego Analytics. Wysoki
udział zdarzeń kluczowych, również przy Organic Social, wzmacnia potrzebę
sprawdzenia definicji i liczebności próby; nie dowodzi takiej samej liczby
pozyskanych klientów. Brak połączenia AdSense nie przeszkadza w pomiarze zapytań
ofertowych i nie stanowi zalecenia do włączenia reklam wydawcy.

Oficjalne źródło Site Kit na GitHub potwierdza mechanizm istniejącego `gtag`
i provider Contact Form 7 ze zdarzeniem `contact`. To sprawdzenie architektury
projektu, nie wersji i konfiguracji zainstalowanej na stronie. Własnych zdarzeń
nie należy naliczać drugi raz przez równoczesne `gtag` i GTM ani dublować
potwierdzonego formularza dodatkowym `generate_lead`.

Unikalny zasięg reklam na różnych urządzeniach nie jest addytywny. Ta sama osoba
może korzystać z telefonu i komputera. Zasięg i częstotliwość nie wystarczają
do obliczenia kosztu pozyskania leada ani zwrotu z kampanii. Proponowaną strukturę
Ads i mapę zdarzeń opisano w [instrukcji pomiaru i Ads](GOOGLE-POMIAR-I-ADS.md).

## Co pozostało nieweryfikowalne z tego środowiska

Odczyt Google Search, Bing i yoona.pl został odrzucony przez proxy komunikatem
`Tunnel connection failed: 403 Forbidden`. Nie sprawdzono aktualnych SERP-ów,
3–5 rzeczywistych konkurentów, wizytówek, robots.txt, canonicali, mapy strony
ani Core Web Vitals produkcji. Nie podano wymyślonych nazw, pozycji i wyników.
Macierz porównania konkurencji pozostaje jawnie nieuzupełniona w planie SEO.

Trzy eksporty nie zawierają raportu cyber_Folks. Nie oznaczono jego nieznanych
zaleceń jako naprawionych. Odblokowanie sieci i dostęp administracyjny do usług
Google są odrębnymi warunkami: publiczny odczyt nie daje prawa technicznego do
zmian na koncie, a zapis konfiguracji środowiska sam nie wykonuje publikacji.

## Odtworzenie i prywatność

`tools/analyze_growth_exports.py` odczytuje CSV-y w pamięci i nie wypakowuje
członków ZIP do ścieżek archiwum. Nie wysyła żądań sieciowych, nie używa kluczy
API i nie loguje się do usług Google. Wyniki zapisuje w osobnym prywatnym
katalogu poza Git; odrzuca ścieżki wewnątrz checkoutu, także przez dowiązanie.
Rozmiary archiwów i członków CSV są ograniczone.

Prywatny JSON zawiera sumy, wybrane dane źródłowe i sumy SHA-256 wejść.
Wersja publiczna zawiera metodologię i działania redakcyjne. Przygotowany pakiet
treści służy do porównania adresów oraz tytułów i nie potwierdza wdrożenia
na żywym WordPressie.
