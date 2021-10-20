/*global M*/
M.mod_rquiz = {};

M.mod_rquiz.init_editpage = function (Y) {
    "use strict";
    var rquizhelper = {
        lastradio: null,

        highlight_correct: function () {
            Y.all('.rquiz_answerradio').each(function (radiobtn) {
                var textbox, lastradio;
                if (radiobtn.get('checked')) {
                    textbox = Y.one('#id_answertext_' + radiobtn.get('value'));
                    if (textbox && textbox.get('value') === '' && this.lastradio) {
                        lastradio = Y.one('#' + this.lastradio);
                        if (lastradio) {
                            lastradio.set('checked', true);
                            lastradio.ancestor().ancestor().addClass('rquiz_highlight_correct');
                        }
                    } else {
                        radiobtn.ancestor().ancestor().addClass('rquiz_highlight_correct');
                        this.lastradio = radiobtn.get('id');
                    }
                } else {
                    radiobtn.ancestor().ancestor().removeClass('rquiz_highlight_correct');
                }
            }, this);
        },

        init: function () {
            Y.all('.rquiz_answerradio').on('click', this.highlight_correct, this);
            this.highlight_correct();
        }
    };

    rquizhelper.init();
};
