function locationCard(settings) {

    var _options = $.fn.extend({ //合并配置参数
        ids: [],
        onProvinceSelect: function (name) { },
        onCitySelect: function (name) { },
        onCountySelect: function (name) { }
    }, settings);
    var provinceAndCity = _areaselect_data;
    var provinceInputId = _options.ids[0], cityInputId = _options.ids[1], countyInputId = _options.ids[2];
    var self = this;
    var pdiv, cdiv, xdiv;
    var p_list = getProvinceList(), c_list = [], x_list = [];

    var pInput = $('#' + provinceInputId);
    var cInput = $('#' + cityInputId);
    var xInput = countyInputId ? $('#' + countyInputId) : null;

    var p_pos = pInput.offset(), c_pos, x_pos;

    var cur_p = pInput.val() ? pInput.val() : null,
        cur_c = cInput.val() ? cInput.val() : null,
        cur_x = countyInputId ? (xInput.val() ? xInput.val() : null) : null;

    var c_css, p_css, x_css;

    /********************************************************************/

    function writeProvinceValue() {
        if (!cur_p) return;
        pInput.val(cur_p);
        cInput.val('');
        xInput.val('');
    }
    function writeCityValue() {
        if (!cur_c) return;
        cInput.val(cur_c);
        xInput.val('');
    }
    function writeCountyValue() {
        if (!cur_x) return;
        xInput.val(cur_x);
    }

    //显示城市列表
    function showCityList(province) {
        clearCards(); //确保展开卡片的时候，把其他卡片隐藏掉
        hideProvinceList();
        hideCountyList();
        if (!cur_p || cur_p.length == 0) { return; }
        c_list = getCityList(cur_p);
        cInput.show();

        c_pos = c_pos || cInput.offset();
        c_css = c_css || {
            'top': c_pos.top + cInput.outerHeight()+6 + 'px',
            'left': c_pos.left -10+ 'px'
        };
        createC(c_list).css(c_css).show();
    }
    //显示省份列表
    function showProvinceList() {
        clearCards(); //确保展开卡片的时候，把其他卡片隐藏掉
        hideCityList();
        hideCountyList();

        p_css = p_css || {
            /*'top': p_pos.top + pInput.outerHeight() + 'px',
            'left': p_pos.left + 'px'*/
            'top': p_pos.top + pInput.outerHeight()+6 + 'px',
            'left': p_pos.left-12+ 'px'
        };
        createP().css(p_css).show();
    }
    //显示县级列表
    function showCountyList() {
    /*    if (!countyInputId) { return; }
        clearCards(); //确保展开卡片的时候，把其他卡片隐藏掉
        hideProvinceList();
        hideCityList();
        if (!cur_p || cur_p.length == 0 || !cur_c || cur_c.length == 0) { return; }
        x_list = getCountyList(cur_p, cur_c);
        if (x_list == undefined) { //如果是直辖市，则不存在县级
            xInput.hide();
            return;
        }
        xInput.show();
        x_pos = x_pos || xInput.offset();
        x_css = x_css || {
            'top': x_pos.top + xInput.outerHeight()+6 + 'px',
            'left': x_pos.left-10 + 'px'
        };
        createX(x_list).css(x_css).show();*/
    }
    //隐藏省份列表
    function hideProvinceList() {
        if (pdiv && pdiv.length > 0) {
            pdiv.hide();
        }
    }

    //隐藏城市列表
    function hideCityList() {
        if (cdiv && cdiv.length > 0) {
            cdiv.hide();
        }
    }
    //隐藏县级列表
    function hideCountyList() {
        if (xdiv && xdiv.length > 0) {
            xdiv.hide();
        }
    }
    //获取省份列表
    function getProvinceList() {
        return provinceAndCity['p'];
    }
    //获取城市列表
    function getCityList(province) {
        return provinceAndCity['c'][province];
    }
    //获取县级列表
    function getCountyList(ps, cs) {
        return provinceAndCity['a'][ps + '-' + cs];
    }

    //创建县级列表
    function createX(countys) {
        var html = '';
        //如果创建过
        if (xdiv && xdiv.length > 0) {
            for (var i = 0; i < countys.length; i++) {
                html += '<a class="a" href="javascript:;">' + countys[i] + '</a>';
            }
            xdiv.html(html);
            return xdiv;
        }
        //如果没有创建过
        html += '<div id="__' + countyInputId + '" class="countys clearfix" style="display:none;">';
        for (var i = 0; i < countys.length; i++) {
            html += '<a class="a" href="javascript:;">' + countys[i] + '</a>';
        }
        html += '</div>';
        xdiv = $(html);
        xdiv.delegate('a', 'click', function (event) {
            var target = $(this);
            cur_x = target.text();
            writeCountyValue();
            hideCountyList();
            event.stopPropagation(); //阻止事件冒泡
        }).appendTo('body');
        return xdiv;
    }
    //创建城市列表
    function createC(citys) {
        var html = '';
        //如果创建过
        if (cdiv && cdiv.length > 0) {
            for (var i = 0; i < citys.length; i++) {
                html += '<a class="a" href="javascript:;">' + citys[i] + '</a>';
            }
            cdiv.html(html);
            return cdiv;
        }
        //如果没有创建过
        html += '<div id="__' + cityInputId + '" class="citys clearfix" style="display:none;">';
        for (var i = 0; i < citys.length; i++) {
            html += '<a class="a" href="javascript:;">' + citys[i] + '</a>';
        }
        html += '</div>';
        cdiv = $(html);
        cdiv.delegate('a', 'click', function (event) {
            var target = $(this);
            cur_c = target.text();
            writeCityValue();
            hideCityList();
            showCountyList();
            event.stopPropagation(); //阻止事件冒泡
        }).appendTo('body');
        return cdiv;
    }
    //创建省份列表
    function createP() {
        //如果创建过
        if (pdiv && pdiv.length > 0) {
            return pdiv;
        }
        //如果没有创建过
        var html = '<div id="__' + provinceInputId + '" class="privinces clearfix" style="display:none;">';
        for (var i = 0; i < p_list.length; i++) {
            html += '<a class="a" href="javascript:;">' + p_list[i] + '</a>';
        }
        html += '</div>';
        pdiv = $(html);
        if ($('#__' + provinceInputId).length == 0) {
            pdiv.delegate('a', 'click', function (event) {
                var target = $(this);
                cur_p = target.text();
                writeProvinceValue(cur_p);
                showCityList(cur_p);
                event.stopPropagation(); //阻止事件冒泡
            }).appendTo('body');
        }
        return pdiv;
    }
    this.setCardsHide = function () {
        hideCountyList();
        hideCityList();
        hideProvinceList();
    }
    function clearCards() {
        for (var i = 0; i < self.cards.length; i++) {
            if (self.id != i) {
                self.cards[i].setCardsHide();
            }
        }
    }
    this.init = function () {
        pInput.click(function (event) {
            showProvinceList();
            event.stopPropagation(); //阻止事件冒泡
        });
        cInput.click(function (event) {
            showCityList();
            event.stopPropagation(); //阻止事件冒泡
        });
        if (xInput) {
            xInput.click(function (event) {
                showCountyList();
                event.stopPropagation(); //阻止事件冒泡
            });
        }
            
        $(document).delegate('html', 'click', function (event) {
            var target = $(event.target);
            //console.log(target);
            if (target.closest('#__' + cityInputId).length == 0 && target.closest('#__' + provinceInputId).length == 0 && target.closest('#__' + countyInputId).length == 0) {
                hideCountyList();
                hideCityList();
                hideProvinceList();
            }
        });
        this.id = this.cards.length;
        //console.log(this.id);
        this.cards.push(this);
    }
}
locationCard.prototype = {
    cards: [] //保存卡片实例
}


