var importer = {
	initProgress: function() {
		if (! importer.progress) {
			importer.progress = {
				bar: document.getElementById("progress_indicator_bar"),
				text: document.getElementById("progress"),
				title: document.getElementById("progress_indicator_title"),
			}
		}
	},

	setProgress: function(percent) {
		importer.initProgress();
		if (percent < 0) {
			importer.progress.bar.parentNode.style.display = "none";
			importer.progress.title.style.display = "none";
			return;
		}
		if (percent > 100) { percent = 100;	/* don't be embarrassing! */ }

		importer.progress.bar.parentNode.style.display = "block";
		importer.progress.bar.style.width = percent + "%";
		importer.progress.title.innerHTML = "&nbsp;" + parseInt(percent) + "%";
	},

	setProgressTitle: function(title) {
		importer.initProgress();
		importer.progress.title.innerHTML = title;
		importer.progress.title.style.display = "block";
	}
};
