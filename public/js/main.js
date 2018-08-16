var app = {
	init: function() {
		this.autocompleteTags.init();
	},

	autocompleteTags: {
		init: function() {
			// https://jqueryui.com/autocomplete/#multiple-remote
			$(".autocomplete-tags")
				.on("keydown", function(e) {
					if(e.keyCode === $.ui.keyCode.TAB && $(this).autocomplete("instance").menu.active) {
						e.preventDefault();
					}
				})
				.autocomplete({
					source: function(req, res) {
						$.getJSON(Routing.generate("app_tag_autocomplete"), {
							term: app.autocompleteTags.utils.extractLast(req.term)
						}, res);
					},
					search: function() {
						var term = app.autocompleteTags.utils.extractLast(this.value);
						if(term.length < 1) {
							return false;
						}
					},
					focus: function() {
						return false;
					},
					select: function(e, ui) {
						var terms = app.autocompleteTags.utils.split(this.value);
						terms.pop();
						terms.push(ui.item.value);
						terms.push("");
						this.value = terms.join(" ");
						return false;
					}
				})
			;
		},
		utils: {
			extractLast: function(term) {
				return this.split(term).pop();
			},

			split: function(val) {
				return val.split(/\s+/);
			}
		}
	}
};

$(document).ready(function() {
	app.init();
});
