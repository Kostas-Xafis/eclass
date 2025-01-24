//check plugins
function check() {
	BrowserDetect.init();

	var br = BrowserDetect.browser;
	var ver = BrowserDetect.version;
	ver = parseFloat(ver);

	// Browser CHECK //
	// ////////////////
	if (
		(br == "Firefox" && ver >= 6) ||
		(br == "Safari" && ver >= 4) ||
		br == "Chrome" ||
		(br == "Explorer" && ver >= 7) ||
		br == "Opera"
	) {
		$("#browsersupported").show();
	} else {
		$("#browsersupported").show();
	} // ;

	// Acrobat Reader CHECK //
	// ///////////////////////

	if (detectAcrobat()) $("#acrobatreaderinstalled").show();
	else $("#acrobatreadernotinstalled").show();

	// Flash Player CHECK //
	// /////////////////////
	if (detectFlash()) $("#flashplayerinstalled").show();
	else $("#flashplayernotinstalled").show();

	// Schockwave Player CHECK //
	// /////////////////////
	if (detectShockwave()) $("#shockinstalled").show();
	else $("#shocknotinstalled").show();

	var a = $("#acrobatreadernotinstalled").css("display");
	var f = $("#flashplayernotinstalled").css("display");
	var b = $("#browsernotsupported").css("display");
	var s = $("#shocknotinstalled").css("display");

	if (a == "none" && f == "none" && b == "none" && s == "none") {
		$("#OK").show();
		$("#notOK").hide();
	}
}

function detectShockwave() {
	if (window.ActiveXObject) {
		try {
			control = new ActiveXObject("SWCtl.SWCtl");
			version = control.ShockwaveVersion("").split("r");
			version = parseFloat(version[0]);
			if (version >= 10) return true;
		} catch (e) {
			return false;
		}
	} else {
		for (var i = 0; i < navigator.plugins.length; i++) {
			if (navigator.plugins[i].name == "Shockwave Flash") return true;
		}
		return false;
	}
}

function detectFlash() {
	if (window.ActiveXObject) {
		try {
			control = new ActiveXObject("ShockwaveFlash.ShockwaveFlash");
			version = control.GetVariable("$version").substring(4);
			version = version.split(",");
			version = parseFloat(version[0] + "." + version[1]);
			if (version >= 10) return true;
		} catch (e) {
			return false;
		}
	} else {
		for (var i = 0; i < navigator.plugins.length; i++) {
			if (navigator.plugins[i].name == "Shockwave Flash") return true;
		}
		return false;
	}
}

function detectAcrobat() {
	if (window.ActiveXObject) {
		var control = null;
		var version = false;
		try {
			// AcroPDF.PDF is used by version 7 and later
			control = new ActiveXObject("AcroPDF.PDF");
		} catch (e) {
			// Do nothing
		}

		if (!control) {
			for (var x = 2; x < 10; x++) {
				try {
					oAcro = eval("new ActiveXObject('PDF.PdfCtrl." + x + "');");
					if (oAcro) {
						version = true;
					}
				} catch (e) {}
			}

			try {
				oAcro4 = new ActiveXObject("PDF.PdfCtrl.1");
				if (oAcro4) {
					version = true;
				}
			} catch (e) {}

			try {
				oAcro7 = new ActiveXObject("AcroPDF.PDF.1");
				if (oAcro7) {
					version = true;
				}
			} catch (e) {}
			return version;
		}

		if (control) {
			version = control.GetVersions().split(",");
			version = version[0].split("=");
			version = parseFloat(version[1]);

			return true;
		} else {
			return false;
		}
	} else {
		for (var i = 0; i < navigator.plugins.length; i++) {
			// alert(navigator.plugins[i].name);
			if (navigator.plugins[i].name == "Adobe Acrobat" || navigator.plugins[i].name == "Chrome PDF Viewer") return true;
		}
		return false;
	}
}

var BrowserDetect = {
	init: function () {
		this.browser = this.searchString(this.dataBrowser) || "An unknown browser";
		this.version = this.searchVersion(navigator.userAgent) || this.searchVersion(navigator.appVersion) || "an unknown version";
		this.OS = this.searchString(this.dataOS) || "an unknown OS";
	},
	searchString: function (data) {
		for (var i = 0; i < data.length; i++) {
			var dataString = data[i].string;
			var dataProp = data[i].prop;
			this.versionSearchString = data[i].versionSearch || data[i].identity;
			if (dataString) {
				if (dataString.indexOf(data[i].subString) != -1) return data[i].identity;
			} else if (dataProp) return data[i].identity;
		}
	},
	searchVersion: function (dataString) {
		var index = dataString.indexOf(this.versionSearchString);
		if (index == -1) return;
		return parseFloat(dataString.substring(index + this.versionSearchString.length + 1));
	},
	dataBrowser: [
		{
			string: navigator.userAgent,
			subString: "Chrome",
			identity: "Chrome"
		},
		{
			string: navigator.userAgent,
			subString: "OmniWeb",
			versionSearch: "OmniWeb/",
			identity: "OmniWeb"
		},
		{
			string: navigator.vendor,
			subString: "Apple",
			identity: "Safari",
			versionSearch: "Version"
		},
		{
			prop: window.opera,
			identity: "Opera",
			versionSearch: "Version"
		},
		{
			string: navigator.vendor,
			subString: "iCab",
			identity: "iCab"
		},
		{
			string: navigator.vendor,
			subString: "KDE",
			identity: "Konqueror"
		},
		{
			string: navigator.userAgent,
			subString: "Firefox",
			identity: "Firefox"
		},
		{
			string: navigator.vendor,
			subString: "Camino",
			identity: "Camino"
		},
		{
			// for newer Netscapes (6+)
			string: navigator.userAgent,
			subString: "Netscape",
			identity: "Netscape"
		},
		{
			string: navigator.userAgent,
			subString: "MSIE",
			identity: "Explorer",
			versionSearch: "MSIE"
		},
		{
			string: navigator.userAgent,
			subString: "Gecko",
			identity: "Mozilla",
			versionSearch: "rv"
		},
		{
			// for older Netscapes (4-)
			string: navigator.userAgent,
			subString: "Mozilla",
			identity: "Netscape",
			versionSearch: "Mozilla"
		}
	],
	dataOS: [
		{
			string: navigator.platform,
			subString: "Win",
			identity: "Windows"
		},
		{
			string: navigator.platform,
			subString: "Mac",
			identity: "Mac"
		},
		{
			string: navigator.userAgent,
			subString: "iPhone",
			identity: "iPhone/iPod"
		},
		{
			string: navigator.platform,
			subString: "Linux",
			identity: "Linux"
		}
	]
};

// let fd = new FormData();
// fd.append("delete", "Διαγραφή");
// fd.append("token", "76b3f681b4fc4e5fcac88715a819d907");

// for (let i = 151; i <= 300; i++) {
// 	try {
// 		await fetch(`http://127.0.0.1/modules/course_info/delete_course.php?course=DIT${100 + i}`, {
// 			method: "POST",
// 			body: fd
// 		});
// 	} catch (e) {}
// }
// Bασικές έννοιες και εργαλεία DevOps|DIT250|Anargyros Tsadimas
// Αλγόριθμοι και Πολυπλοκότητα|DIT110|Δημήτριος Μιχαήλ
// Ανάλυση Συστημάτων και Τεχνολογία Λογισμικού|DIT187|Κλεοπάτρα Μπαρδάκη
// Ανάλυση Συστημάτων και Τεχνολογία Λογισμικού|DIT122|Κωνσταντίνα Βασιλοπούλου
// Ανάλυση Συστημάτων και Τεχνολογία Λογισμικού - Νικολαϊδου|DIT187|Μαρία Νικολαίδη
// Ανάπτυξη Κινητών Εφαρμογών|DIT117|Ευθύμιος Χονδρογιάννης
// Αντικειμενοστραφής Προγραμματισμός ΙΙ|DIT130|Ιωάννης Βιόλος
// Αντικειμενοστρεφής Προγραμματισμός Ι|DIT112|Κλεοπάτρα Μπαρδάκη
// Αντικειμενοστρεφής Προγραμματισμός ΙΙ|ΥΠ10|Άγγελος Χαραλαμπίδης
// Αξιολόγηση Συστημάτων και Διεπαφών|ΥΠ30|Γεωργία Δέδε
// Απόδοση Συστημάτων|DIT145|Δημοσθένης Αναγνωστόπουλος
// Αποτίμηση  επενδύσεων ΤΠΕ ΕΠ23|DIT134|Χρήστος Μιχαλακέλης
// Αριθμητική Ανάλυση|DIT154|Χρήστος Μιχαλακέλης
// Αρχιτεκτονική Υπολογιστών|DIT106|Παναγιώτης Μιχαήλ
// Ασφάλεια Πληροφοριακών Συστημάτων|DIT146|Βασιλική Ανδρόνικου
// Ασφάλεια Πληροφοριακών Συστημάτων|DIT203|Παναγιώτης Ριζομυλιώτης
// Ασφάλεια Πληροφοριακών Συστημάτων|DIT195|Στέφανος Γκρίτζαλης
// Αυτοματοποίηση Επιχειρηματικών Διαδικασιών|DIT219|Αγγελική Καραγιαννάκη
// Βάσεις Δεδομένων|DIT105|Ηρακλής Βαρλάμης
// Γραφικά Υπολογιστών|DIT147|Γεώργιος Συμιακάκης
// ΔΙΑΚΡΙΤΑ ΜΑΘΗΜΑΤΙΚΑ|DIT144|Μαλβίνα Βαμβακάρη
// ΔΙΑΧΕΙΡΙΣΗ ΔΙΚΤΥΩΝ ΒΑΣΙΣΜΕΝΩΝ ΣΤΟ ΛΟΓΙΣΜΙΚΟ|DIT255|Ηλίας Παναγιωτόπουλος
// Διαχείριση Δικτύων Βασισμένων στο Λογισμικό 2023-24|DIT289|ΕΙΡΗΝΗ ΛΙΩΤΟΥ
// Διαχείριση Επιχειρηματικών Διαδικασιών στην Εφοδιαστική Αλυσίδα|DIT222|Κλεοπάτρα Μπαρδάκη
// Διαχείριση Υπολογιστικού Νέφους|DIT266|Ιωάννης Βιόλος
// Διδακτική ρομποτικής και εκπαίδευση STEM|DIT239|Ευαγγελία Φιλιοπούλου
// Διδακτική της Πληροφορικής|DIT201|Χρύσα Σοφιανοπούλου
// Δίκτυα ΙΙ|DIT193|Γεώργιος Δημητρακόπουλος
// Δίκτυα Υπολογιστών 2024-25|DIT298|Ειρήνη Λιώτου
// Διοίκηση Έργων Πληροφορικής|DIT124|Κωνσταντίνα Βασιλοπούλου
// Διοίκηση Έργων Πληροφορικής (Ακαδ. Έτος 2023-2024) |DIT227|ΜΑΥΡΕΤΑ ΣΤΑΜΑΤΗ
// Δομές Δεδομένων|DIT141|Δημήτριος Μιχαήλ
// Εξόρυξη δεδομένων|DIT129|Ηρακλής Βαρλάμης
// Επικοινωνία Ανθρώπου - Μηχανής|DIT237|Γεώργιος Παπαδόπουλος
// Εφαρμογές Τηλεματικής στις Μεταφορές και στην Υγεία|DIT159|Γεώργιος Δημητρακόπουλος
// Ηλεκτρονική Διακυβέρνηση και Ψηφιακός Μετασχηματισμός|DIT278|ΜΑΥΡΕΤΑ ΣΤΑΜΑΤΗ
// Ηλεκτρονική και Εφαρμογές στο Διαδίκτυο των Πραγμάτων|DIT125|Θωμάς Καμαλάκης
// Ηλεκτρονικό Εμπόριο|DIT120|Κωνσταντίνα Βασιλοπούλου
// Κατανεμημένα Συστήματα|DIT138|Νικολαΐδου Μάρα
// Κοινωνία και Τεχνολογίες Πληροφορίας και Επικοινωνιών ΤΠΕ|DIT158|Χρύσα Σοφιανοπούλου
// Κρυπτογραφία|DIT208|Παναγιώτης Ριζομυλιώτης
// Λειτουργικά Συστήματα|DIT136|Μάρα Νικολαϊδου
// Λογική Σχεδίαση|DIT131|Φραγκιαδάκης Γιώργος
// Μεθοδολογία Επιστημονικής Έρευνας|DIT157|Χρύσα Σοφιανοπούλου
// Μεταγλωττιστές|ΕΠ33|Άγγελος Χαραλαμπίδης
// Μεταγλωττιστές|DIT107|Δημήτριος Μιχαήλ
// Μηχανική Μάθηση και Εφαρμογές|DIT232|Χρήστος Δίου
// Οικονομικά της Ψηφιακής Τεχνολογίας|DIT194|Χρήστος Μιχαλακέλης
// Οπτικές Επικοινωνίες|DIT126|Θωμάς Καμαλάκης
// Παιδαγωγική Ψυχολογία|DIT175|Αικατερίνη Αντωνοπούλου
// Παράλληλοι Υπολογιστές και Αλγόριθμοι|DIT139|Παναγιώτης Μιχαήλ
// ΠΙΘΑΝΟΤΗΤΕΣ|DIT155|Μαλβίνα Βαμβακάρη
// Πληροφοριακά Συστήματα|DIT234|ΜΑΥΡΕΤΑ ΣΤΑΜΑΤΗ
// Πληροφοριακά Συστήματα και Ηλεκτρονικό Επιχειρείν|DIT277|ΜΑΥΡΕΤΑ ΣΤΑΜΑΤΗ
// ΠΛΗΡΟΦΟΡΙΚΗ ΚΑΙ ΕΚΠΑΙΔΕΥΣΗ|DIT127|Χρύσα Σοφιανοπούλου
// Προγραμματισμός II|DIT116|Κωνσταντίνος Τσερπές
// Προγραμματισμός II|DIT301|Γεώργιος Παπαδόπουλος
// Προγραμματισμός Ι|DIT135|Χρήστος Δίου
// Προγραμματισμός Συστημάτων|DIT137|Ιωάννης Βιόλος
// Προγραμματισμός Συστημάτων|DIT276|ΧΡΗΣΤΟΣ ΑΝΔΡΙΚΟΣ
// Προηγμένα Θέματα Λειτουργικών Συστημάτων|DIT270|ΧΡΗΣΤΟΣ ΑΝΔΡΙΚΟΣ
// Προσομοίωση|DIT113|Δημοσθένης Αναγνωστόπουλος
// Προχωρημένα Θέματα Ψηφιακής Σχεδίασης|DIT254|Νικόλαος Ζομπάκης
// Πτυχιακές εργασίες|DIT283|Παναγιώτης Ριζομυλιώτης
// Σήματα και Συστήματα|DIT108|Παναγιώτης Ριζομυλιώτης
// ΣΤΑΤΙΣΤΙΚΗ|DIT142|Μαλβίνα Βαμβακάρη
// Σύγχρονες Αρχιτεκτονικές Υπολογιστών|DIT207|Σωτήριος Ξύδης
// Συστήματα Κινητών Επικοινωνιών|DIT151|Γεώργιος Δημητρακόπουλος
// Συστήματα Λήψης Αποφάσεων|DIT228|Γεωργία Δέδε
// Συστήματα Λήψης Αποφάσεων 2023|DIT273|Ιωάννης Βιόλος
// Σχεδίαση Βάσεων Δεδομένων και Κατανεμημένες ΒΔ|DIT128|Βασίλης Ευθυμίου
// Τεχνητή Νοημοσύνη|DIT231|Χρήστος Δίου
// Τεχνολογίες Γραφημάτων και Εφαρμογές|DIT263|Δημήτριος Μιχαήλ
// Τεχνολογίες Διαδικτύου|DIT160|Γεώργιος Δημητρακόπουλος
// Τεχνολογίες Διαδικτύου 2024-25|DIT299|Ειρήνη Λιώτου
// Τεχνολογίες Εφαρμογών Ιστού|DIT102|Κωνσταντίνος Τσερπές
// Τηλεπικοινωνιακά Δίκτυα|DIT148|Ιωάννης Νεοκοσμίδης
// Τηλεπικοινωνιακά Συστήματα|DIT149|Καμαλάκης Θωμάς
// Τηλεπικοινωνίες|DIT132|Χρήστος Μιχαλακέλης
// ΥΠΗΡΕΣΙΕΣ ΚΑΙ ΣΥΣΤΗΜΑΤΑ ΔΙΑΔΙΚΤΥΟΥ (Πρώην ΑΣΤΙΚΟΣ ΥΠΟΛΟΓΙΣΜΟΣ)|DIT220|Γεώργιος Κουσιουρής
// ΥΠΟΛΟΓΙΣΤΙΚΑ ΜΑΘΗΜΑΤΙΚΑ|DIT202|Χρήστος Μιχαλακέλης
// Ψηφιακές Δορυφορικές Επικοινωνίες|DIT236|Vassilis Dalakas
// Ψηφιακή Επεξεργασία Εικόνας και Εφαρμογές|DIT271|Γεώργιος Παπαδόπουλος
// Ψηφιακή Τεχνολογία και Εφαρμογές Τηλεματικής|DIT111|Δημοσθένης Αναγνωστόπουλος
