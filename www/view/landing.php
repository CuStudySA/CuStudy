<div id="heading">
	<div id="heading-content">
		<a href="/" class="logo-link"><img src="/resources/img/landing-logo-header.svg" alt="CuStudy logó"><h1>CuStudy</h1></a>
		<a href="/login" class="login-wrap" rel="nofollow"><span class="btn typcn typcn-key">Bejelentkezés</span></a>
	</div>
</div>
<div id="cliche-cover">
	<div class="content">
		<div class="content-container">
			<h2>Vége az elfelejtett házi feladatoknak!<span class="desktop-only"> Rendszerünk segít, hogy minden fontos iskolai teendőről időben értesülj.</span></h2>
			<p>Szoftverünk a magyar diákok igényeire szabva segíti a mindennapi felkészülést számtalan diák számára.</p>
			<span class="btn typcn typcn-arrow-down scrolldown"></span>
		</div>
		<svg version="1.1" opacity="0.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve">
			<line id="clock-h" fill="none" stroke="#FFF" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" x1="50" y1="50" x2="73" y2="50"></line>
			<line id="clock-m" fill="none" stroke="#FFF" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" x1="50" y1="50" x2="50" y2="5"></line>
			<circle fill="none" stroke="#FFF" stroke-width="8" stroke-miterlimit="10" cx="50" cy="50" r="45"></circle>
		</svg>
		<script src="/resources/js/min/web-animations.js"></script>
		<script>
			var hours = document.getElementById("clock-h"),
				mins = document.getElementById("clock-m");
			hours.animate(
				[
					{transform: 'rotate(0deg)'},
					{transform: 'rotate(360deg)'}
				],
				{ duration: 8400, iterations: Infinity }
			);
			mins.animate(
				[
					{transform: 'rotate(0deg)'},
					{transform: 'rotate(360deg)'}
				],
				{ duration: 700, iterations: Infinity }
			);
		</script>
	</div>
</div>
<div id="main-content">
	<section>
		<div class="margin">
			<div class="image">
				<img src="/resources/img/landing/clean_interface.png" alt="Képernyőkép">
			</div>
			<div class="text">
				<h3>Letisztult kezelőfelület</h3>
				<p>A CuStudy felhasználó felületét úgy alkottuk meg, hogy az minnél kevesebb üres teret tartalmazzon. Szerintünk a látható területet nem ürességgel, hanem hasznos információval kell feltölteni. A lágy sötétkék témaszín nyugalmat sugároz az egész programon át, kevésbé kellemetlenné téve a közelgő témazáró dolgozatok látványát.</p>
			</div>
		</div>
	</section>
	<section>
		<div class="margin">
			<div class="text">
				<h3>Minden egy helyen</h3>
				<p>A számodra legfontosabb információk közvetlenül belépés után a főoldalon azonnal eléd tárulnak, nem szükséges a menüpontok sokaságán átvergődni magad, ha csak a legfontosabbak érdekelnek. Az elkészítésre váró házi feladatatok, a következő tanítási nap órarendje és a közelgő események erőfeszítés nélkül elérhetőek.</p>
			</div>
			<div class="image">
				<img src="/resources/img/landing/all_in_one.png" alt="Képernyőkép">
			</div>
		</div>
	</section>
	<section>
		<div class="margin">
			<div class="image align-center">
				<span class="typcn typcn-device-desktop"></span>
				<span class="typcn typcn-arrow-sync rotate-slowly"></span>
			</div>
			<div class="text">
				<h3>Szoftver, mint szolgáltatás</h3>
				<p>Felhasználóinknak nem kell szerverre beruházni a szoftver futtatásához, vagy intézményüknek külön személyzetet felvenni azért, hogy a rendszert karban tartsák. A CuStudy-t a fejlesztők közvetlenül tartják formában, az osztályszintű adminisztrációs feladatokat pedig maguk a diákok végzik. Ki is tudná az osztály ügyeit jobban kezelni, mint maguk ők maguk?</p>
			</div>
		</div>
	</section>
	<section class="noimgshadow">
		<div class="margin">
			<div class="text">
				<h3>Remek, újabb jelszó&hellip;</h3>
				<p>A szoftverünk más szolgáltatók segítségével is képes a felhasználók azonosítására, így <strong>nincs szükség egy újabb jelszó megjegyzésére</strong>, elég egy már megszokott szolgáltatónál lévő fiók jelszavát fejben tartani. A bejelentkező oldalon a Google, Facebook, Microsoft és további szolgáltatókon keresztül is be lehet lépni a rendszerbe, miután a felhasználó ezt a profilján engedélyezi.</p>
			</div>
			<div class="image">
				<img src="/resources/img/landing/extlogin.png" alt="Képernyőkép">
			</div>
		</div>
	</section>
	<section>
		<div class="margin">
			<div class="image align-center">
				<span class="typcn typcn-user"></span>
				<span class="typcn typcn-lock-closed"></span>
			</div>
			<div class="text">
				<h3>Biztonság mindenekelőtt</h3>
				<p>A fejlesztőcsapat mindent megtesz a felhasználók adatainak védelmében. A web böngésződ TLS titkosítás segítségével kommunikál a szerverünkkel, így az adatokat támadók nem tudják kiolvasni a hálózati forgalomból. A belépéshez használt jelszó szerverünkön visszafejthetetlen titkosítással kerül tárolásra, így azt mi sem tudjuk elolvasni. Szolgáltatásunk DDoS és CSRF támadások ellen is védett. Az adatokat nem adjuk ki harmadik fél számára, ami a CuStudy-ban történik, az a CuStudy-ban is marad.</p>
			</div>
		</div>
	</section>
	<footer>
		<p>CuStudy és CuStudy logó &copy; CuStudy Software Alliance 2015-<?=date('Y')?> &mdash; Minden jog fenntartva.</p>
		<p>A külső szolgáltatók ikonjai a hozzájuk tartozó szervezetek jogtulajdonát képezik.</p>
	</footer>
</div>
