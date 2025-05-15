$(".showpass").on("click", function () {
    $(this).toggleClass("show");
    if ($(this).hasClass("show")) {
        $(this).closest(".input-text").find(".password").attr("type", "text");
    } else {
        $(this).closest(".input-text").find(".password").attr("type", "password");
    }
});
$(document).on("click", ".checked-item", function () {
    $(this).addClass("active").siblings().removeClass("active");
});
$(".numinput").on("keypress", function (e) {
    const charCode = e.which ? e.which : e.keyCode;
    if (String.fromCharCode(charCode).match(/[^0-9-۰-۹]/g)) return false;
});
$(".arzinput").on("keypress", function (e) {
    const charCode = e.which ? e.which : e.keyCode;
    if (String.fromCharCode(charCode).match(/[^0-9-۰-۹\.]/g)) return false;
});
$(".changnum").on("keyup", function () {
    var prNam = ["۰", "۱", "۲", "۳", "۴", "۵", "۶", "۷", "۸", "۹"];
    var enNum = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"];
    var numVall = $(this).val();
    for (var i = 0; i < 10; i++) {
        var regex_fa = new RegExp(prNam[i], "g");
        numVall = numVall.replace(regex_fa, enNum[i]);
    }
    $(this).val(numVall);
    console.log($(this).val());
});
function countdown() {
    var timer2 = "1:59";
    var interval = setInterval(function () {
        var timer = timer2.split(":");
        var minutes = parseInt(timer[0], 10);
        var seconds = parseInt(timer[1], 10);
        --seconds;
        minutes = seconds < 0 ? --minutes : minutes;
        if (minutes < 0) clearInterval(interval);
        seconds = seconds < 0 ? 59 : seconds;
        seconds = seconds < 10 ? "0" + seconds : seconds;
        $(".timer").html(minutes + ":" + seconds);
        timer2 = minutes + ":" + seconds;
        if (seconds == 0) {
            clearInterval(interval);
            document.getElementById("timer").innerHTML = "00:00";
            $("#resendCode").css("display", "block");
            $(".resendcode").css("display", "none");
        }
    }, 1000);
}
$("#resendCode").on("click", function () {
    countdown();
    $(".resendcode").fadeIn();
    $(this).css("display", "none");
});
$(".select-coin-active").on("click", function () {
    $(this).siblings(".select-coin-list").slideToggle();
});
$(".select-coin-item").on("click change", function () {
    $(this).addClass("active");
    $(this).siblings(".select-coin-item").removeClass("active");
    var coinSelect = $(this).find(".coin-select").html();
    $(this).parent().siblings(".select-coin-active").find(".coin-select").html(coinSelect);
    var selectSymbol = $(".select-coin-item.active").data("symbol");
    console.log(selectSymbol);
    new TradingView.MediumWidget({
        symbols: [[selectSymbol]],
        chartOnly: true,
        width: "100%",
        height: "300",
        locale: "en",
        colorTheme: "dark",
        autosize: false,
        showVolume: false,
        hideDateRanges: false,
        hideMarketStatus: true,
        hideSymbolLogo: false,
        scalePosition: "left",
        scaleMode: "Logarithmic",
        fontFamily: "-apple-system, BlinkMacSystemFont, Trebuchet MS, Roboto, Ubuntu, sans-serif",
        fontSize: "10",
        noTimeScale: false,
        valuesTracking: "1",
        changeMode: "price-and-percent",
        chartType: "area",
        gridLineColor: "rgba(255, 255, 255, 0.05)",
        backgroundColor: "rgba(255, 255, 255, 0)",
        lineColor: "rgba(255, 204, 128, 0.4)",
        topColor: "rgba(255, 204, 128, 0.6)",
        bottomColor: "rgba(255, 204, 128, 0)",
        dateFormat: "MMM dd",
        lineWidth: 1,
        container_id: "candleChart",
    });
});
$(window).each(function () {
    $(".select-coin-item:first-child").click();
});
$("body").on("click", function (event) {
    if (!$(event.target).closest(".select-coins").length) {
        $(".select-coin-list").slideUp();
    }
});
$(".tab-item").on("click", function () {
    const $this = $(this);
    const $container = $this.closest(".tab-container");
    const activeTab = $this.attr("data-tab-box");
    $this.addClass("active").siblings(".tab-item").removeClass("active");
    $container.find(`#${activeTab}`).addClass("active").slideDown().siblings(".tab-content").removeClass("active").slideUp();
});
$(".tab-container").each(function () {
    const $container = $(this);
    const $activeItem = $container.find(".tab-item.active");
    if ($activeItem.length) {
        const activeTab = $activeItem.attr("data-tab-box");
        $container.find(`#${activeTab}`).addClass("active").slideDown().siblings(".tab-content").removeClass("active").slideUp();
    } else {
        const $firstItem = $container.find(".tab-item:first-child");
        const firstTab = $firstItem.attr("data-tab-box");
        $firstItem.addClass("active");
        $container.find(`#${firstTab}`).addClass("active").slideDown().siblings(".tab-content").removeClass("active").slideUp();
    }
});
console.log(localStorage.getItem("toggleMenu"));
let sidebarActive = $(".sidebar");
if (localStorage.getItem("toggleMenu") === "true") {
    sidebarActive.addClass("active");
}
$(".menu-toggle").on("click", function () {
    sidebarActive.toggleClass("active");
    localStorage.setItem("toggleMenu", sidebarActive.hasClass("active"));
});
$(".closemenu").on("click", function () {
    sidebarActive.removeClass("active");
});
if ($(window).width() <= 1024) {
    $(".sidebar.active").removeClass("active");
    $("body").on("click", function (event) {
        if (!$(event.target).closest(".sidebar.active , .menu-toggle").length) {
            $(".sidebar.active").removeClass("active");
            $("body").css("overflow", "auto");
        }
    });
    $(".menu-toggle").on("click", function () {
        $("body").css("overflow", "hidden");
    });
    $(".closemenu").on("click", function () {
        $("body").css("overflow", "auto");
    });
}
$(window).on("resize", function () {
    if ($(window).width() <= 1024) {
        sidebarActive.removeClass("active");
    }
});

// $(document).on("click", ".copy", function () {
//     var copyText = $(this).siblings(".copyme");
//     var $temp = $("<input>");
//     $("body").append($temp);
//     $temp.val($(copyText).text()).select();
//     document.execCommand("copy");
//     $temp.remove();
//     cuteToast({ type: "success", title: "با موفقیت کپی شد", message: $(copyText).text(), timer: 3500 });
// });

$('.copy').on('click', function () {
    var copyText = $(this).siblings('.copyme');
    var textToCopy = $(copyText).text();

    navigator.clipboard.writeText(textToCopy)
        .then(function () {
            cuteToast({
                type: 'success',
                title: 'با موفقیت کپی شد',
                message: textToCopy,
                timer: 3500,
            });
        })
        .catch(function (err) {
            console.error('خطا در کپی متن به Clipboard: ', err);
        });
});

// $(document).ready(function() {
//     $(document).on("click", ".copy", function () {
//         var copyText = $(this).siblings(".copyme")[0];
//         var range = document.createRange();
//         range.selectNode(copyText);
//         window.getSelection().removeAllRanges();
//         window.getSelection().addRange(range);
//
//         try {
//             document.execCommand("copy");
//             cuteToast({ type: "success", title: "با موفقیت کپی شد", message: copyText.textContent, timer: 3500 });
//         } catch (err) {
//             console.error("کپی انجام نشد: ", err);
//         }
//
//         window.getSelection().removeAllRanges();
//     });
// });



$(".bookmark").on("click", function () {
    {
        $(this).toggleClass("active");
    }
});
$(".td-discrip").on("click", function () {
    let disMessage = $(this).data("discrip");
    cuteToast({ type: "info", title: "پیام سیستم", message: disMessage, timer: 3500 });
});

$(".select").on("click", function (event) {
    if (!$(event.target).closest(".select-off").length) {
        $(this).siblings(".select-list").slideToggle();
        $(this).parents().siblings().find(".select-list").slideUp();
    }
});



$(".select-option").on("click", function () {
    var inputImg = $(this).find("img").attr("src");
    $(this).addClass("active").siblings().removeClass("active");
    $(this).closest('.select-box').find(".select-input").val($(this).text());
    var imgInput = $(this).closest(".select-box").find(".imginput");
    if (imgInput.length > 0) {
        imgInput.attr("src", inputImg);
    }

    $(".select-list").slideUp();
    setTimeout(() => {
        if ($(this).closest(".select-box").find(".select-input:input[required]").length > 0) {
            $(this).closest(".select-box").find(".select-input").trigger('input')
        }
    }, 0);
    var selectData = $(this).attr("data-select");
    $(this).closest(".select-box").find(".selectval").val(selectData);
    
    if ($(this).closest(".select-box").find(".select-input:input[required]").length > 0) {
        $(this).closest(".select-box").find(".select").removeClass("error");
        $(this).closest(".select-box").find(".select").addClass("valid");
    }

    if (!$(".backwent").hasClass("active")) {
        $("#bargasht").attr("disabled", true).parent(".input-text").css({ opacity: "0.3" });
        $("#bargasht").closest(".airsearch-box").find(".submitbtn").attr("disabled", false);
    } else {
        $("#bargasht").attr("disabled", false).parent(".input-text").css({ opacity: "1" });
    }
});

$(".select-option.active").each(function () {
    var inputImg = $(this).find("img").attr("src");
    $(this).closest('.select-box').find(".select-input").val($(this).text());
    $(this).closest(".select-box").find(".imginput").attr("src", inputImg);
});

$(document).ready(function () {
    $(".select-option").each(function () {
        var val = $(this).closest(".select-box").find(".selectval").val();
        if ($(this).data("select") == val) {
            $(this).addClass("active");
        }
    });
    $(".select-option.active").each(function () {
        var selectActive = $(this).text();
        $(this).closest(".select-box").find(".select-input").val(selectActive);
    });
});

// $(".select-option").on("click", function () {
//     var inputImg = $(this).find("img").attr("src");
//     $(this).closest('.select').find(".select-img").attr("src", inputImg);
// });

$("body").on("click", function (event) {
    if (!$(event.target).closest(".select-box").length) {
        $(".select-list").slideUp();
    }
});
$(".userimginput").on("change", function (event) {
    $(".userimg").attr("src", URL.createObjectURL(event.target.files[0]));
    $("#userImag").submit();
});
$(".imginp").on("change", function (event) {
    $(this).parent(".uploadimg").siblings(".imgprev").find(".img").find("img").attr("src", URL.createObjectURL(event.target.files[0]));
    $(this).parent().siblings(".imgprev").slideDown(1000).css("display", "flex");
    $(this).parent().slideUp(1000);
});
$(".remove-img").on("click", function () {
    $(this).parent().slideUp(1000);
    $(this).parent().siblings(".uploadimg").slideDown(1000);
    $(this).parent().siblings(".uploadimg").find(".imginp").val(null);
});
$(".sub-toggle").on("click", function (event) {
    if (!$(event.target).closest(".sub-box").length) {
        $(this).find(".sub-box").slideToggle();
    }
    $(this).siblings(".sub-toggle").find(".sub-box").slideUp();
});
$("body").on("click", function (event) {
    if (!$(event.target).closest(".sub-box , .sub-toggle").length) {
        $(".sub-box").slideUp();
    }
});
$(".pinlogin-field").on("keyup", function () {
    if ($(this).hasClass("invalid")) {
        $(".pinlogin-error").fadeIn();
    } else {
        $(".pinlogin-error").fadeOut();
    }
});
$(".file-input").on("change", function () {
    var filename = $(this).val();
    $(this).siblings(".filenameinput").html(filename);
});
$(".star").click(function () {
    $(this).prevAll(".star").addClass("selected");
    $(this).nextAll(".star").removeClass("selected");
    $(this).addClass("selected");
});
$(".star").hover(
    function () {
        $(this).prevAll(".star").css("color", "#fc0");
        $(this).nextAll(".star").css("color", "#8F959E");
    },
    function () {
        $(this).nextAll(".star").css("color", "");
        $(this).prevAll(".star").css("color", "");
    }
);
const links = document.querySelectorAll(".scrollbtn");
for (let link of links) {
    link.addEventListener("click", (e) => {
        $(link).addClass("active");
        $(link).siblings().removeClass("active");
        const article = document.querySelector(`.scrollin:nth-of-type(${e.target.dataset.id})`);
        article.scrollIntoView({ behavior: "smooth", block: "center", inline: "nearest" });
    });
}
$(".searchinput").on("keyup", function () {
    var value = $(this).val().toLowerCase();
    $(this)
        .closest(".searchbox")
        .find(".searchitem")
        .filter(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
});
$("[data-priceval]").on("click", function () {
    var dataVal = $(this).data("priceval");
    $("#addInput").val(dataVal);
});
$(".addbalance").on("click", function () {
    const wlBalance = $(this).closest("form").find(".form-balance").text();
    $(this).closest("form").find(".coinvalue").val(wlBalance);
    $(this).closest("form").find(".coinvalue").trigger('input');
    if ($(".coinvalue").hasClass("valid") && $(".coinvalue").val()) {
        $(".coinvalue").closest(".input-text").addClass("valid");
    } else {
        $(".coinvalue").closest(".input-text").addClass("error");
    }
});
$(".md-open").on("click", function () {
    var dataModal = $(this).data("modal");
    $("." + dataModal)
        .fadeIn()
        .css("display", "flex");
    setTimeout(() => {
        $(".modal").css("transform", "scale(1)");
    }, 0);
    $("body").addClass("noscroll");
});
$(".md-close").on("click", function () {
    $(this).closest(".modalbox").fadeOut();
    setTimeout(() => {
        $(this).closest(".modal").css("transform", "scale(0.8)");
    }, 0);
    $("body").removeClass("noscroll");
});
$(".modalbox").on("click", function (event) {
    if (!$(event.target).closest(".modal").length) {
        $(".modalbox").fadeOut();
        setTimeout(() => {
            $(".modal").css("transform", "scale(0.8)");
        }, 0);
        $("body").removeClass("noscroll");
    }
});
$(".sendcode").on("click", function (event) {
    event.preventDefault();
    $(this).closest("form").find(".codeinput").focus();
    var timeNum = $(this).find("span");
    var sendbtn = $(this);
    var codeTimer = "1:59";
    var interval = setInterval(function () {
        var timer = codeTimer.split(":");
        var minutes = parseInt(timer[0], 10);
        var seconds = parseInt(timer[1], 10);
        --seconds;
        minutes = seconds < 0 ? --minutes : minutes;
        if (minutes < 0) clearInterval(interval);
        seconds = seconds < 0 ? 59 : seconds;
        seconds = seconds < 10 ? "0" + seconds : seconds;
        $(timeNum).html(minutes + ":" + seconds);
        codeTimer = minutes + ":" + seconds;
        $(sendbtn).attr("disabled", true);
        if ((seconds == 0) & (minutes == 0)) {
            clearInterval(interval);
            $(timeNum).html("ارسال کد");
            $(sendbtn).attr("disabled", false);
        }
    }, 1000);
});
$(document).each(function () {
    var travellerNum = $("#travellerNum");
    $(".plus").click(function () {
        if ($(this).next().val() < 30) {
            $(this)
                .next()
                .val(+$(this).next().val() + 1);
        }
        $(this)
            .parents()
            .find(travellerNum)
            .val(+$(this).parents().find(travellerNum).val() + 1);
        $(this).siblings(".minus").attr("disabled", false);
        $(".numtrl").text(travellerNum.val());
    });
    $(".minus").click(function () {
        if ($(this).prev().val() > 0) {
            if ($(this).prev().val() > 0)
                $(this)
                    .prev()
                    .val(+$(this).prev().val() - 1);
        }
        $(this)
            .parents()
            .find(travellerNum)
            .val(+$(this).parents().find(travellerNum).val() - 1);
        if ($(this).prev().val() == 0) {
            $(this).attr("disabled", true);
        }
        if ($(".adult").prev().val() == 1) {
            $(".adult").attr("disabled", true);
        }
        $(".numtrl").text(travellerNum.val());
    });
});
$(".togglebox").on("click", function () {
    $(this).toggleClass("active");
    if ($(".showmemo").hasClass("active")) {
        $(this).closest("form").find(".memo-tag").slideDown();
        $('[name="memo"]').attr('value', '');
    } else {
        $(this).closest("form").find(".memo-tag").slideUp();
        $('[name="memo"]').attr('value', '0');
    }
});
$(".timeslider").on("click", ".timeitem", function () {
    $(this).addClass("active");
    $(this).parent().siblings().find(".timeitem").removeClass("active");
    $(this).parent().siblings().removeClass("active");
    $(this).parent().addClass("active");
});
$(".file-input").on("change", function (event) {
    var filename = $(this).val();
    $(this).siblings(".filenameinput").html(filename);
});
$(".card-remove").on("click", function () {
    $(this).closest("tr").remove();
});
$(".btn-bill").on("click", function () {
    $(this).closest("form").valid();
    if ($(this).closest("form").validate().checkForm()) {
        $(".ghabz-info").slideDown();
        $(".btn-payment").show();
        $(this).hide();
    }
});
$(".drop-toggle").on("click", function () {
    $(this).closest(".drop-item").toggleClass("active");
    $(this).siblings().slideToggle();
    $(this).closest(".drop-item").siblings().find(".drop-slide").slideUp();
    $(this).closest(".drop-item").siblings().removeClass("active");
});
$(".numto-fa").on("keyup change", function () {
    $(this).closest("form").find(".fanum").slideDown();
    $(this)
        .closest("form")
        .find(".fanum")
        .html($(this).val().num2persian() + " " + "تومان");
});
$(document).on("click", ".vpric-item", function () {
    setTimeout(() => {
        $(this).closest("form").find(".fanum").slideDown();
        $(this)
            .closest("form")
            .find(".fanum")
            .html($(".numto-fa").val().num2persian() + " " + "تومان");
        $(this).closest("form").find(".numto-fa").trigger('input');
        if ($(this).closest("form").validate().checkForm()) {
            $(this).closest("form").find(".submitbtn").attr("disabled", false);
            $(this).closest("form").find(".numto-fa").parent(".input-text").removeClass("error").addClass("valid");
        }
    }, 0);
});
let lightMode = $("body");
let darLToggle = $(".dark-toggle");
if (localStorage.getItem("lightMode") === "true") {
    lightMode.addClass("light");
    darLToggle.addClass("active");
}
$(darLToggle).on("click", function () {
    darLToggle.toggleClass("active");
    lightMode.toggleClass("light");
    localStorage.setItem("lightMode", lightMode.hasClass("active"));
    localStorage.setItem("lightMode", darLToggle.hasClass("active"));
});
console.log(localStorage.getItem("lightMode"));

$(".copy-val").on("click", function () {
    var inputCopy = $(this).closest(".input-text").find(".input-copy");
    var inputValue = $(inputCopy).val();
    if (navigator.clipboard) {
        navigator.clipboard.writeText(inputValue).then(function () {
            cuteToast({
                type: "success",
                title: "با موفقیت کپی شد",
                message: inputValue,
                timer: 3500
            });
        }).catch(function () {

            copyFallback(inputValue);
        });
    } else {

        copyFallback(inputValue);
    }
});

$(document).ready(function() {
    $('.paste-val').click(function() {
        var inputField = $(this).siblings('input');

        if (navigator.clipboard) {
            navigator.clipboard.readText().then(function(clipboardText) {
                inputField.val(clipboardText);
                $(this).siblings('input').trigger('input');
            }).catch(function(err) {
                console.error('Unable to read clipboard', err);
            });
        } else {
            var pasteEvent = (event) => {
                event.preventDefault();
                var clipboardText = event.originalEvent.clipboardData.getData('text');
                inputField.val(clipboardText);
                $(this).siblings('input').trigger('input');
                $(document).off('paste', pasteEvent);
            };
            $(document).on('paste', pasteEvent);
            try {
                document.execCommand('paste');
            } catch (err) {
                console.error('Unable to execute paste command', err);
            }
        }
    });
});

function copyFallback(text) {
    var $inputValue = $("<input>");
    $("body").append($inputValue);
    $inputValue.val(text).select();
    document.execCommand("copy");
    $inputValue.remove();

    cuteToast({
        type: "success",
        title: "با موفقیت کپی شد",
        message: text,
        timer: 3500
    });
}


function dollarPricePrint(price) {
    var Price = parseFloat(price);
    var Float = 0;
    if (Number.isInteger(Price)) {
        return Price.toString();
    }
    if (Price < 0.00000001) {
        return "0 ~";
    }
    var ranges = [
        { min: 0.0000000001, max: 0.000000001, float: 9 },
        { min: 0.000000001, max: 0.00000001, float: 9 },
        { min: 0.00000001, max: 0.0000001, float: 8 },
        { min: 0.0000001, max: 0.000001, float: 7 },
        { min: 0.000001, max: 0.00001, float: 6 },
        { min: 0.00001, max: 0.0001, float: 5 },
        { min: 0.0001, max: 0.001, float: 4 },
        { min: 0.001, max: 100, float: 3 },
        { min: 100, max: 300, float: 1 },
        { min: 300, max: Infinity, float: 0 },
    ];
    for (var i = 0; i < ranges.length; i++) {
        if (Price >= ranges[i].min && Price < ranges[i].max) {
            Float = ranges[i].float;
            break;
        }
    }
    var formattedPrice = Price.toLocaleString("en-US", { maximumFractionDigits: Float });
    return formattedPrice;
}
function rialPricePrint(dataRial) {
    dataRial = parseFloat(dataRial);
    if (dataRial == 0) {
        return 0;
    }
    if (dataRial < 0.005) {
        return "0 ~";
    }
    if (dataRial >= 0.005 && dataRial < 1) {
        Float = 3;
    }
    if (dataRial >= 1 && dataRial < 100) {
        Float = 2;
    }
    if (dataRial >= 100 && dataRial < 500) {
        Float = 1;
    }
    if (dataRial >= 500) {
        Float = 0;
    }
    var strRial = dataRial.toFixed(Float).toString();
    var strRialParts = strRial.split(".");
    strRialParts[0] = strRialParts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    return strRialParts.join(".");
}
function keepOnlyDecimalAndNumber(inputString) {
    const regex = /[^0-9.]/g;
    const cleanedString = inputString.replace(regex, "");
    return cleanedString;
}
if ("OTPCredential" in window) {
    window.addEventListener("DOMContentLoaded", (e) => {
        const input = document.querySelector('input[autocomplete="one-time-code"]');
        if (!input) return;
        const ac = new AbortController();
        const form = input.closest("form");
        if (form) {
            form.addEventListener("submit", (e) => {
                ac.abort();
            });
        }
        navigator.credentials
            .get({ otp: { transport: ["sms"] }, signal: ac.signal })
            .then((otp) => {
                input.value = otp.code;
                if (form) form.submit();
            })
            .catch((err) => {
                console.log(err);
            });
    });
}
if (!$(".imgprev").hasClass("active")) {
} else {
    $(".uploadimg").slideUp();
}
$(".sub").on("click", function () {
    if (!$(event.target).closest(".submenu").length) {
        $(this).find(".submenu").slideToggle();
    }
});
$("body").on("click", function (event) {
    if (!$(event.target).closest(".sub").length) {
        $(".submenu").slideUp();
    }
});
var langPref = localStorage.getItem("langPref");
if (langPref) {
    if (langPref === "en") {
        $(".change-name .fa-name").show();
        $(".change-name .en-name").hide();
        $(".btn-lang").removeClass("active");
    } else {
        $(".change-name .fa-name").hide();
        $(".change-name .en-name").show();
        $(".btn-lang").addClass("active");
    }
}
$(".btn-lang").on("click", function () {
    if ($(this).hasClass("active")) {
        $(".change-name .fa-name").hide();
        $(".change-name .en-name").show();
        localStorage.setItem("langPref", "fa");
    } else {
        $(".change-name .fa-name").show();
        $(".change-name .en-name").hide();
        localStorage.setItem("langPref", "en");
    }
});

// $("td[data-pricedollar]").each(function () {
//     var tdDollarPrice = $(this).data("data-pricedollar");
//     var pricFix = keepOnlyDecimalAndNumber(tdDollarPrice);
//     $(this).attr("data-pricedollar", pricFix);
// });
if (localStorage.getItem("toggleActive") === "true") {
    $(".hidelow").addClass("active");
}
$(".hidelow").on("click", function () {
    $("td[data-pricedollar]").each(function () {
        var lowValue = $(this).data("pricedollar");
        if ($(".hidelow").hasClass("active") && lowValue < 1) {
            $(this)
                .filter('[data-pricedollar="' + lowValue + '"]')
                .closest("tr")
                .hide();
        } else {
            $(this)
                .filter('[data-pricedollar="' + lowValue + '"]')
                .closest("tr")
                .show();
        }
        console.log(lowValue);
    });
    localStorage.setItem("toggleActive", $(this).hasClass("active"));
});

$("td[data-pricedollar]").each(function () {
    var lowValue = $(this).data("pricedollar");
    if ($(".hidelow").hasClass("active") && lowValue < 1) {
        $(this)
            .filter('[data-pricedollar="' + lowValue + '"]')
            .closest("tr")
            .hide();
    } else {
        $(this)
            .filter('[data-pricedollar="' + lowValue + '"]')
            .closest("tr")
            .show();
    }
    console.log(lowValue);
});


$(".addcama").on("keyup change blur", function () {
    $(this).val(
        $(this)
            .val()
            .toString()
            .replace(/\D/g, "")
            .replace(/\B(?=(\d{3})+(?!\d))/g, ",")
    );
});
$(".notifi-list .notifi-item").each(function () {
    $(this).attr("id", $(this).attr("id") + "1");
});
$(".notifi-item").each(function () {
    var notifiID = $(this).attr("id");
    if (localStorage.getItem("readPMs-" + notifiID)) {
        $("#" + notifiID).addClass("show");
        $("#" + notifiID).removeClass("noshow");
    }
    $("#" + notifiID).on("click", function () {
        $(this).addClass("show");
        $(this).removeClass("noshow");
        $(this).find(".drop-slide").slideToggle();
        $(this).siblings(".notifi-item").find(".drop-slide").slideUp();
        localStorage.setItem("readPMs-" + notifiID, $(this).hasClass("show"));
    });
    $(".showall").on("click", function () {
        $("#" + notifiID).addClass("show");
        $("#" + notifiID).removeClass("noshow");
        $(".notifi-new").hide();
        localStorage.setItem("readPMs-" + notifiID, $(this).hasClass("show"));
    });
    if ($(".notifi-list .notifi-item.noshow")[0]) {
        $(".notifi-new").show();
    } else {
        $(".notifi-new").hide();
    }
});
$(document).ready(function () {
    resizeDiv();
    $(window).resize(resizeDiv);
});
function resizeDiv() {
    var sourceWidth = $(".dataTables_scrollBody table").width();
    var targetWidth = Math.max(sourceWidth, 300);
    $(".dataTables_scrollHeadInner").css("min-width", targetWidth);
}
function handleInput() {
    const hamrahaval = ["0910", "0911", "0912", "0913", "0914", "0915", "0916", "0917", "0918", "0919", "0990", "0991", "0992", "0993", "0994"];
    const irancell = ["0901", "0902", "0903", "0904", "0905", "0930", "0933", "0935", "0936", "0937", "0938", "0939", "0941"];
    const prNam = ["۰", "۱", "۲", "۳", "۴", "۵", "۶", "۷", "۸", "۹"];
    const enNum = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"];
    const input = $(".mobinput")
        .val()
        .replace(new RegExp(prNam.join("|"), "g"), (m) => enNum[prNam.indexOf(m)]);
    console.log("Input after replace: " + input);
    const firstFourDigits = input.substring(0, 4);
    if (firstFourDigits.length === 4) {
        if (irancell.some((code) => code === firstFourDigits)) {
            setActive(".tab-hamrahaval", false);
            setActive(".tab-irancell", true);
            $(".tab-irancell").click();
            $(".nohamrah").show();
        } else if (hamrahaval.some((code) => code === firstFourDigits)) {
            setActive(".tab-irancell", false);
            setActive(".tab-hamrahaval", true);
            $(".tab-hamrahaval").click();
            $(".nohamrah").hide();
        } else {
            cuteToast({ type: "error", title: "پیام سیستم", message: "شماره وارد شده متعلق به اپراتورهای ایرانسل و همراه اول نمی‌باشد", timer: 5000 });
        }
    }
}
function setActive(selector, isActive) {
    $(selector).toggleClass("active", isActive);
}
$(document).on("click", ".lastmob", function () {
    $(this).closest("form").find(".mobinput").val($(this).text());
    $(this).closest("form").find(".mobinput").parent(".input-text").removeClass("error").addClass("valid");
    setTimeout(() => {
        $(this).closest("form").find(".mobinput").trigger('input');
        if ($(this).closest("form").validate().checkForm()) {
            $(this).closest("form").find(".submitbtn").attr("disabled", false);
        }
    }, 0);
    handleInput();
});
document.addEventListener("focusin", function () {
    document.documentElement.style.zoom = 1;
});
$("#modalForm").on("click", ".coin-item", function () {
    $(".coin-item").removeClass("active");
    $(this).addClass("active");
});


$(".uploadfile").each(function() {
    var $fileInput = $(this).closest(".file-input");

    var $address = $(this).closest(".file-input").find(".file-address");

    $(this).click(function() {
        $fileInput.find(".file-val").click();
    });

    $address.click(function() {
        $fileInput.find(".file-val").click();
    });

    $fileInput.find(".file-val").change(function() {
        $(this).closest(".file-input").find(".file-address").val($(this).val());
        if ($(this).closest(".file-input").find(".file-img").length > 0) {
            $(this).closest(".file-input").find(".file-img").attr('src', URL.createObjectURL(event.target.files[0]))
        }
    });

    // Add drag and drop support
    $fileInput.on("dragover", function(e) {
        e.preventDefault();
        $(this).addClass("dragover");
    });

    $fileInput.on("dragleave", function() {
        $(this).removeClass("dragover");
    });

    $fileInput.on("drop", function(e) {
        e.preventDefault();
        $(this).removeClass("dragover");

        var file = e.originalEvent.dataTransfer.files[0];
        $fileInput.find(".file-val")[0].files = e.originalEvent.dataTransfer.files;
        if ($(this).closest(".file-input").find(".file-img").length > 0) {
            $(this).closest(".file-input").find(".file-img").attr('src', URL.createObjectURL(file));
        }
        $address.val(file.name);
    });

});