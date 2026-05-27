"use strict";

/* FECHAR O SPLASH */
$(document).ready(function() {
    $('.splash').removeClass('active');
});
/* FECHAR O SPLASH */

/* MENU LATERAL */
function togglemenu() {

    var e = $(window)[0].innerWidth;

    if( (e <= 1090 && e >= 992  && $(".msbmenu-navbar")) ||  localStorage.getItem('mobileMenu') == 'true'){
        $(".msbmenu-navbar").addClass("navbar-collapsed")
    } else {
        $(".msbmenu-navbar").removeClass("navbar-collapsed")
    }

    if(localStorage.getItem('mobileMenu') == 'true'){
        $(".mobile-menu").addClass("on")
    } else {
        $(".mobile-menu").removeClass("on")
    }
}

function collapsehover() {
    if (1 == $(".msbmenu-navbar").hasClass("navbar-collapsed")) {
        smmenu($(".msbmenu-navbar.navbar-collapsed"))
    }
}

function smmenu(e) {
    e.off("click").off("mouseenter mouseleave").hover(function() {
        $(".msbmenu-navbar.navbar-collapsed:not(.theme-horizontal)").find("li.msbmenu-trigger").children(".msbmenu-submenu").slideDown()
    }, function() {
        $(".msbmenu-navbar.navbar-collapsed:not(.theme-horizontal)").find("li.msbmenu-trigger").children(".msbmenu-submenu").slideUp()
    })
}


$(document).ready(function() {

    togglemenu(), collapsehover(), ($("body").hasClass("layout-6"));
    var a = $(window)[0].innerWidth;
    if (
        $(".mobile-menu").on("click", function() {
            $(this).toggleClass("on")
        }),
        $("#mobile-collapse").on("click", function() {
            if(localStorage.getItem('mobileMenu') != 'true'){
                localStorage.setItem('mobileMenu', true);
            } else {
                localStorage.setItem('mobileMenu', false);
            }


            $(".msbmenu-navbar:not(.theme-horizontal)").toggleClass("navbar-collapsed"), collapsehover()
        })
    )

    if (!$(".msbmenu-navbar").hasClass("theme-horizontal")) {
        var a = $(window)[0].innerWidth;
        if ($(".navbar-content")[0])
            if (a < 992 || $(".msbmenu-navbar").hasClass("menupos-static")) {
                new PerfectScrollbar(".navbar-content", {
                    wheelSpeed: .5,
                    swipeEasing: 0,
                    suppressScrollX: !0,
                    wheelPropagation: 1,
                    minScrollbarLength: 40
                })
            } else {
                new PerfectScrollbar(".navbar-content", {
                    wheelSpeed: .5,
                    swipeEasing: 0,
                    suppressScrollX: !0,
                    wheelPropagation: 1,
                    minScrollbarLength: 40
                })
            }
    }

    $("#mobile-collapse1").click(function(e) {
        let o = $(window)[0].innerWidth;

        if(o <= 992){
            $(".msbmenu-navbar.menupos-fixed").toggleClass("mob-open")
        }
    })

    $("body").on("click", function(e) {
        let o = $(window)[0].innerWidth;
        if(o <= 992 && $('.mob-open') && !document.querySelector(".mob-open").contains(e.target) && !document.querySelector("#mobile-collapse1").contains(e.target)){
            $(".msbmenu-navbar.menupos-fixed").toggleClass("mob-open")
            $("#mobile-collapse1").removeClass("on")
        }
    })


    $("li.msbmenu-hasmenu").on("click", function(e) {
        if($(this).children("ul.msbmenu-submenu").is(":visible")){
            $(this).children("ul.msbmenu-submenu").slideUp()
        } else {
            $(this).children("ul.msbmenu-submenu").slideDown()
        }
    })

}), $(window).resize(function() {
    togglemenu(), collapsehover(), ($("body").hasClass("layout-6"))
});

/* FIM DO MENU LATERAL */
$('body').on('change', '[name=orgao_atual]', function(){
    let id_orgao = this.value
    Swal.fire({
        title: 'Mudar de órgão',
        text: "Você tem certeza que deseja alterar o seu órgão atual?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sim, alterar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.value) {
            $.post(`${window.route().t.url}/${window.route().t.routes['alterar.orgao'].uri}`, {orgao_id: id_orgao}, function(data){
                window.location.reload();
            })
        }
    });
})

$(document).ready(function() {

    try {
        $('.select2').select2();
    } catch (e) {

    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $('body').tooltip({
        selector: '[data-bs-toggle=tooltip]',
        boundary: 'window'
    });

    $('html').popover({
        container: "body",
        selector: "[data-bs-toggle=popover-hover]",
        trigger: "hover",
        html: true
    });

    $('body').on('mouseleave, click', '[data-bs-toggle="tooltip"]', function() {
        $(this).tooltip('hide');
    })

    $('body').on('click', '.card-header-right .full-card', function() {
        let $this = $(this);
        let port = $($this.parents('.card'));
        port.toggleClass("full-card");
        $(this).toggleClass("fa-window-restore");
    });
    $('body').on('click', '.card-header-right .minimize-card', function() {
        let $this = $(this);
        let port = $($this.parents('.card'));
        let card = $(port).children('.card-body').slideToggle();
        $(this).toggleClass("fa-minus").fadeIn('slow');
        $(this).toggleClass("fa-plus").fadeIn('slow');
    });
    $('body').on('click', '.card-header-right .reload-card', function() {
        var $this = $(this);
        $this.parents('.card').addClass("card-load");
        $this.parents('.card').append('<div class="card-loader"><i class="fa-solid fa-spinner-third fa-spin"></div>');
        setTimeout(function() {
            $this.parents('.card').children(".card-loader").remove();
            $this.parents('.card').removeClass("card-load");
        }, 3000);
    });
    $('body').on('click', '.card-header-right .close-card', function() {
        var $this = $(this);
        $this.parents('.card').animate({
            'opacity': '0',
            '-webkit-transform': 'scale3d(.3, .3, .3)',
            'transform': 'scale3d(.3, .3, .3)'
        });

        setTimeout(function() {
            $this.parents('.card').remove();
        }, 100);
    });


    $('body').on('keyup', '[mascara=moeda]', function() {
        let $this = $(this);
        let valor = $this.val();

        try {
            valor = valor.replace(/[^0-9]/g,'');
        } catch (e) {

        }

        valor = parseInt(valor == '' ? 0 : valor).toString();

        if(valor.length < 4){
            valor = ("000" + (valor)).slice(-3)
        }

        let decimal = valor.substr(valor.length - 2);
        let inteiro = valor.slice(0, -2);
        valor = parseFloat(inteiro + "." + decimal);

        valor = (valor).toLocaleString('pt-BR', {
                    maximumFractionDigits: 2,
                    minimumFractionDigits: 2
                })


        $this.val(valor);
    });
});

function number_format(number, decimals = 2) {
    let v = number;
    try {
        v = v.replace(/[^0-9]/g,'');
    } catch (e) {

    }
    v = parseFloat(v);

    return (v).toLocaleString('pt-BR', {
        maximumFractionDigits: decimals,
        minimumFractionDigits: decimals
    });
}


function startLoading(c,t) {
    if( t ) {
        let d =  $(c).closest('.card-loading')
        if(!d.hasClass('card-load')) {
            d.addClass("card-load")
            d.append('<div class="card-loader"><i class="fa-solid fa-spinner-third fa-spin"></div>')
        }
    } else {
        let d = $(c).closest('.card')
        if(!d.hasClass('card-load')) {
            d.addClass("card-load")
            d.append('<div class="card-loader"><i class="fa-solid fa-spinner-third fa-spin"></div>');
        }
    }
}

function stopLoading(c,t) {
    if( t ) {
        $(c).closest('.card-loading').children(".card-loader").remove();
        $(c).closest('.card-loading').removeClass("card-load");
    } else {
        $(c).closest('.card').children(".card-loader").remove();
        $(c).closest('.card').removeClass("card-load");
    }
}





(function(window) {
    'use strict';

    /* MENU USUARIO */
    $(document).on('click', function(e) {
        if ($(e.target).closest('.open-user-menu').length != 0) {
            $('.user-options ul').toggleClass('show');
        } else {
            $('.user-options ul').removeClass('show');
        }
    });

    /* MENU SISTEMAS */
    $(document).on('click', '.open_menu_sistemas', function(e) {
        e.preventDefault();
        $('.menu.sistemas').toggleClass('open');

        if($('.menu.sistemas').hasClass('open')) {
            $('header .open_menu_sistemas i').removeClass('fa-grid-2').addClass('fa-times');
        } else {
            $('header .open_menu_sistemas i').removeClass('fa-times').addClass('fa-grid-2');
        }
    });

    /* SCROLLBAR MENU SISTEMAS */
    if ($(".menu.sistemas").length) {
        new PerfectScrollbar(".menu.sistemas", {
            wheelSpeed: .5,
            swipeEasing: 0,
            suppressScrollX: !0,
            wheelPropagation: 1,
            minScrollbarLength: 40
        })
    }

    /* SCROLLBAR MENU LATERAL */
    if ($(".menu.lateral").length) {
        new PerfectScrollbar(".menu.lateral", {
            wheelSpeed: .5,
            swipeEasing: 0,
            suppressScrollX: !0,
            wheelPropagation: 0,
            minScrollbarLength: 40
        })
    }

    /* EFEITO WAVE */
    var Waves = Waves || {};
    var $$ = document.querySelectorAll.bind(document);

    // Find exact position of element
    function isWindow(obj) {
        return obj !== null && obj === obj.window;
    }

    function getWindow(elem) {
        return isWindow(elem) ? elem : elem.nodeType === 9 && elem.defaultView;
    }

    function offset(elem) {
        var docElem, win,
            box = {top: 0, left: 0},
            doc = elem && elem.ownerDocument;

        docElem = doc.documentElement;

        if (typeof elem.getBoundingClientRect !== typeof undefined) {
            box = elem.getBoundingClientRect();
        }
        win = getWindow(doc);
        return {
            top: box.top + win.pageYOffset - docElem.clientTop,
            left: box.left + win.pageXOffset - docElem.clientLeft
        };
    }

    function convertStyle(obj) {
        var style = '';

        for (var a in obj) {
            if (obj.hasOwnProperty(a)) {
                style += (a + ':' + obj[a] + ';');
            }
        }

        return style;
    }

    var Effect = {

        // Effect delay
        duration: 750,

        show: function(e, element) {

            // Disable right click
            if (e.button === 2) {
                return false;
            }

            var el = element || this;

            // Create ripple
            var ripple = document.createElement('div');
            ripple.className = 'waves-ripple';
            el.appendChild(ripple);

            // Get click coordinate and element witdh
            var pos         = offset(el);
            var relativeY   = (e.pageY - pos.top);
            var relativeX   = (e.pageX - pos.left);
            var scale       = 'scale('+((el.clientWidth / 100) * 10)+')';

            // Support for touch devices
            if ('touches' in e) {
              relativeY   = (e.touches[0].pageY - pos.top);
              relativeX   = (e.touches[0].pageX - pos.left);
            }

            // Attach data to element
            ripple.setAttribute('data-hold', Date.now());
            ripple.setAttribute('data-scale', scale);
            ripple.setAttribute('data-x', relativeX);
            ripple.setAttribute('data-y', relativeY);

            // Set ripple position
            var rippleStyle = {
                'top': relativeY+'px',
                'left': relativeX+'px'
            };

            ripple.className = ripple.className + ' waves-notransition';
            ripple.setAttribute('style', convertStyle(rippleStyle));
            ripple.className = ripple.className.replace('waves-notransition', '');

            // Scale the ripple
            rippleStyle['-webkit-transform'] = scale;
            rippleStyle['-moz-transform'] = scale;
            rippleStyle['-ms-transform'] = scale;
            rippleStyle['-o-transform'] = scale;
            rippleStyle.transform = scale;
            rippleStyle.opacity   = '1';

            rippleStyle['-webkit-transition-duration'] = Effect.duration + 'ms';
            rippleStyle['-moz-transition-duration']    = Effect.duration + 'ms';
            rippleStyle['-o-transition-duration']      = Effect.duration + 'ms';
            rippleStyle['transition-duration']         = Effect.duration + 'ms';

            rippleStyle['-webkit-transition-timing-function'] = 'cubic-bezier(0.250, 0.460, 0.450, 0.940)';
            rippleStyle['-moz-transition-timing-function']    = 'cubic-bezier(0.250, 0.460, 0.450, 0.940)';
            rippleStyle['-o-transition-timing-function']      = 'cubic-bezier(0.250, 0.460, 0.450, 0.940)';
            rippleStyle['transition-timing-function']         = 'cubic-bezier(0.250, 0.460, 0.450, 0.940)';

            ripple.setAttribute('style', convertStyle(rippleStyle));
        },

        hide: function(e) {
            TouchHandler.touchup(e);

            var el = this;
            var width = el.clientWidth * 1.4;

            // Get first ripple
            var ripple = null;
            var ripples = el.getElementsByClassName('waves-ripple');
            if (ripples.length > 0) {
                ripple = ripples[ripples.length - 1];
            } else {
                return false;
            }

            var relativeX   = ripple.getAttribute('data-x');
            var relativeY   = ripple.getAttribute('data-y');
            var scale       = ripple.getAttribute('data-scale');

            // Get delay beetween mousedown and mouse leave
            var diff = Date.now() - Number(ripple.getAttribute('data-hold'));
            var delay = 350 - diff;

            if (delay < 0) {
                delay = 0;
            }

            // Fade out ripple after delay
            setTimeout(function() {
                var style = {
                    'top': relativeY+'px',
                    'left': relativeX+'px',
                    'opacity': '0',

                    // Duration
                    '-webkit-transition-duration': Effect.duration + 'ms',
                    '-moz-transition-duration': Effect.duration + 'ms',
                    '-o-transition-duration': Effect.duration + 'ms',
                    'transition-duration': Effect.duration + 'ms',
                    '-webkit-transform': scale,
                    '-moz-transform': scale,
                    '-ms-transform': scale,
                    '-o-transform': scale,
                    'transform': scale,
                };

                ripple.setAttribute('style', convertStyle(style));

                setTimeout(function() {
                    try {
                        el.removeChild(ripple);
                    } catch(e) {
                        return false;
                    }
                }, Effect.duration);
            }, delay);
        },

        // Little hack to make <input> can perform waves effect
        wrapInput: function(elements) {
            for (var a = 0; a < elements.length; a++) {
                var el = elements[a];

                if (el.tagName.toLowerCase() === 'input') {
                    var parent = el.parentNode;

                    // If input already have parent just pass through
                    if (parent.tagName.toLowerCase() === 'i' && parent.className.indexOf('waves-effect') !== -1) {
                        continue;
                    }

                    // Put element class and style to the specified parent
                    var wrapper = document.createElement('i');
                    wrapper.className = el.className + ' waves-input-wrapper';

                    var elementStyle = el.getAttribute('style');

                    if (!elementStyle) {
                        elementStyle = '';
                    }

                    wrapper.setAttribute('style', elementStyle);

                    el.className = 'waves-button-input';
                    el.removeAttribute('style');

                    // Put element as child
                    parent.replaceChild(wrapper, el);
                    wrapper.appendChild(el);
                }
            }
        }
    };


    var TouchHandler = {
        touches: 0,
        allowEvent: function(e) {
            var allow = true;

            if (e.type === 'touchstart') {
                TouchHandler.touches += 1; //push
            } else if (e.type === 'touchend' || e.type === 'touchcancel') {
                setTimeout(function() {
                    if (TouchHandler.touches > 0) {
                        TouchHandler.touches -= 1; //pop after 500ms
                    }
                }, 500);
            } else if (e.type === 'mousedown' && TouchHandler.touches > 0) {
                allow = false;
            }

            return allow;
        },
        touchup: function(e) {
            TouchHandler.allowEvent(e);
        }
    };


    function getWavesEffectElement(e) {
        if (TouchHandler.allowEvent(e) === false) {
            return null;
        }

        var element = null;
        var target = e.target || e.srcElement;

        while (target.parentElement !== null) {
            if (!(target instanceof SVGElement) && target.className.indexOf('waves-effect') !== -1) {
                element = target;
                break;
            } else if (target.classList.contains('waves-effect')) {
                element = target;
                break;
            }
            target = target.parentElement;
        }

        return element;
    }

    function showEffect(e) {
        var element = getWavesEffectElement(e);

        if (element !== null) {
            Effect.show(e, element);

            if ('ontouchstart' in window) {
                element.addEventListener('touchend', Effect.hide, false);
                element.addEventListener('touchcancel', Effect.hide, false);
            }

            element.addEventListener('mouseup', Effect.hide, false);
            element.addEventListener('mouseleave', Effect.hide, false);
        }
    }

    Waves.displayEffect = function(options) {
        options = options || {};

        if ('duration' in options) {
            Effect.duration = options.duration;
        }

        //Wrap input inside <i> tag
        Effect.wrapInput($$('.waves-effect'));

        if ('ontouchstart' in window) {
            document.body.addEventListener('touchstart', showEffect, false);
        }

        document.body.addEventListener('mousedown', showEffect, false);
    };

    Waves.attach = function(element) {
        if (element.tagName.toLowerCase() === 'input') {
            Effect.wrapInput([element]);
            element = element.parentElement;
        }

        if ('ontouchstart' in window) {
            element.addEventListener('touchstart', showEffect, false);
        }

        element.addEventListener('mousedown', showEffect, false);
    };

    window.Waves = Waves;

    document.addEventListener('DOMContentLoaded', function() {
        Waves.displayEffect();
    }, false);

})(window);




/* MONTAR O CALENDARIO */
$(document).ready(function() {
    if($('.calendario-theme-default').length){
        $('.calendario-theme-default').each(function(){
            let layout = `<div class='clndr-controls'>
                <div><</div>
                <div>Novembro</div>
                <div>></div></div>
            </div>`
        })
    }
});
/* MONTAR O CALENDARIO FIM */


/* CONSTRAST */
(function () {
    var Contrast = {
        storage: 'contrastState',
        cssClass: 'contrast',
        currentState: null,
        check: checkContrast,
        getState: getContrastState,
        setState: setContrastState,
        toogle: toogleContrast,
        updateView: updateViewContrast
    };

    window.toggleContrast = function () { Contrast.toogle(); };

    Contrast.check();

    function checkContrast() {
        this.updateView();
    }

    function getContrastState() {
		if(localStorage.getItem('contrastState') == true) {
			$("#ativarAutoContraste").parent().addClass("active");
		}
        return localStorage.getItem(this.storage) === 'true';
    }

    function setContrastState(state) {
        localStorage.setItem(this.storage, '' + state);
        this.currentState = state;
        this.updateView();
    }

    function updateViewContrast() {
        var body = document.body;

        if (this.currentState === null)
            this.currentState = this.getState();

        if (this.currentState)
            body.classList.add(this.cssClass);
        else
            body.classList.remove(this.cssClass);
    }

    function toogleContrast() {
        this.setState(!this.currentState);
    }
})();


/* AJAX */
window.onload = function () {

    $('body').on('change keyup', '.is-invalid', function() {
        $(this).removeClass('is-invalid');
    });

    $('body').on('submit', 'form.ajax', function(e) {
        e.preventDefault();

        const form = $(this);
        const formData = new FormData(this);
        const url = form.attr('action');
        const method = form.attr('method');
        const formAfter = form.data('callback') ?? null;
        const reset = form.data('reset') ?? false;
        const redirect = form.data('redirect') ?? null;
        let validado = true

        //percorre todos os itens do form
        form.find('input, select, textarea').each(function() {
            if($(this).attr('required') && !$(this).val()){
                let msg = $(this).data('msg-invalid') ?? 'Campo obrigatório';
                $(this).addClass('is-invalid');
                $(this).parent().find('.invalid-feedback').html(msg);
                validado = false;
            }
        });

        if(!validado){
            return false;
        }

        let btn = form.find('button[type="submit"]');
        let btnText = btn.html();

        btn.prop('disabled', true).html(`<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> ${btn.data('loading') ?? 'Aguarde...'}`);

        $.ajax({
            url: url,
            type: method ?? 'POST',
            data: formData,
            dataType: 'json',
            contentType: false,
            processData: false,
            success: function (data) {

                // if(redirect){
                //     return window.location.href = redirect;
                // }

                Swal.fire({
                    icon: 'success',
                    title: data.title ?? 'Sucesso!',
                    text: data.message ?? 'Operação realizada com sucesso.',
                    showConfirmButton: false,
                    timer: 1500,
                    didClose: function(){
                        if(redirect){
                            window.location.href = redirect;
                        }
                    }
                })

                if(data.action || formAfter){
                    if(formAfter && formAfter.substr(-1) != ')') formAfter += '(data)';
                    if(data.action && data.action.substr(-1) != ')') data.action += '(data)';
                    eval(data.action ?? formAfter);
                }

                //limpa o formulário
                if(reset) {
                    form[0].reset();
                }

            },
            error: function (data) {
                if(!data.responseJSON){
                    Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                        }
                    }).fire({
                        icon: 'error',
                        title: 'Ocorreu um erro inesperado. Tente novamente mais tarde.'
                    })
                    return false;
                }
                let content = data.responseJSON;

                if(content.errors) {
                    for(let i in content.errors) {
                        let input = $(`[name="${prepreInput(i)}"]`)
                        if(input.length && input.is(':visible')) {
                            input.addClass('is-invalid');
                            input.parent().find('.invalid-feedback').html(content.errors[i][0]);
                        } else {
                            Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                                didOpen: (toast) => {
                                    toast.addEventListener('mouseenter', Swal.stopTimer)
                                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                                }
                            }).fire({
                                icon: 'error',
                                title: content.errors[i][0] ?? 'Preencha todos os campos obrigatórios'
                            })
                        }
                    }

                    return false
                }


                Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                    }
                }).fire({
                    icon: 'error',
                    title: content.message ?? 'Preencha todos os campos obrigatórios',
                })
            },
            complete: function () {
                btn.prop('disabled', false)
                .html(btnText);
            }
        });

    })

    function prepreInput(val){
        let r = '';
        let v = val.split('.');
        if(v.length > 1){
            for(let i in v) {
                if(i == 0){
                    r += v[i];
                } else {
                    r += `[${v[i]}]`;
                }
            }
        } else {
            r = v[0];
        }

        return r;

    }
};
/* AJAX FIM */


/* MASCARAS */
window.onload = function () {
    try{
        $('.decimal').mask('#.##0,00', {reverse: true});
        $('.cnpj').mask('00.000.000/0000-00', {reverse: true});
        $('.cpf').mask('000.000.000-00', {reverse: true});
        $('.data').mask('00/00/0000');
        $('.date_hora').mask('00/00/0000 00:00');
        $('.cep').mask('00000-000');
        $('.telefone').mask('(00) 0000-0000', {
            onKeyPress: function(val, e, field, options) {
                let mask = '(00) 0000-0000';
                if (val[5] == 9) {
                    mask = '(00) 00000-0000';
                }
                field.mask(mask, options);
            }
        });
    } catch (e) {

    }
}
/* MASCARAS */
