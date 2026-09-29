$(document).ready(function () {

    /* =====================================================
       BASE URL — works for any subfolder like /myshop/mymobileshop/
       ===================================================== */
    var BASE = window.location.pathname.replace(/\/[^\/]*$/, '/');

    /* =====================================================
       CAROUSELS
       ===================================================== */
    if ($("#banner-area .owl-carousel").length) {
        $("#banner-area .owl-carousel").owlCarousel({
            items: 1, loop: true, autoplay: true,
            autoplayTimeout: 3500, dots: true, nav: false
        });
    }
    if ($("#top-sale .owl-carousel").length) {
        $("#top-sale .owl-carousel").owlCarousel({
            loop: true, nav: true, dots: false,
            responsive: { 0:{items:1}, 600:{items:3}, 1000:{items:5} }
        });
    }
    if ($("#new-phones .owl-carousel").length) {
        $("#new-phones .owl-carousel").owlCarousel({
            loop: true, nav: false, dots: true,
            responsive: { 0:{items:1}, 600:{items:3}, 1000:{items:4} }
        });
    }
    if ($("#blogs .owl-carousel").length) {
        $("#blogs .owl-carousel").owlCarousel({
            loop: true, nav: false, dots: true,
            responsive: { 0:{items:1}, 600:{items:2}, 1000:{items:3} }
        });
    }

    /* =====================================================
       ISOTOPE FILTER
       ===================================================== */
    if ($(".grid").length) {
        var $grid = $(".grid").isotope({ itemSelector: '.grid-item', layoutMode: 'fitRows' });
        $(".button-group").on("click", "button", function () {
            $(".button-group button").removeClass("is-checked");
            $(this).addClass("is-checked");
            $grid.isotope({ filter: $(this).attr('data-filter') });
        });
    }

    /* =====================================================
       NAVBAR CART BADGE
       ===================================================== */
    $.get(BASE + "cart_ajax.php", { action: "get_count" }, function(res) {
        if (res && res.count > 0) {
            if ($(".nav-cart-badge").length) {
                $(".nav-cart-badge").text(res.count);
            } else {
                $(".nav-cart-btn").append('<span class="nav-cart-badge">' + res.count + '</span>');
            }
        }
    }, "json").fail(function(){});

    /* =====================================================
       AUTH MODAL — open
       ===================================================== */
    $(document).on("click", "#openAuthModal, .open-auth-modal", function (e) {
        e.preventDefault();
        resetAuthModal();
        var modal = new bootstrap.Modal(document.getElementById("authModal"));
        modal.show();
    });

    /* ---- helpers ---- */
    function showAlert(msg, type) {
        $("#authAlert").removeClass("d-none success error").addClass(type).text(msg);
    }
    function hideAlert() {
        $("#authAlert").addClass("d-none").removeClass("success error");
    }
    function showAllSteps(step) {
        // step: 'step1' | 'step2' | 'forgot'
        $("#authStep1, #authStep2, #authStepForgot").addClass("d-none");
        if (step === 'step1') {
            $("#authStep1").removeClass("d-none");
            $("#authModalTitle").text("Register / Sign in");
            $("#authModalSubtitle").html('<i class="fas fa-check-circle text-success me-1"></i>Your information is protected');
        } else if (step === 'step2') {
            $("#authStep2").removeClass("d-none");
        } else if (step === 'forgot') {
            $("#authStepForgot").removeClass("d-none");
            $("#authModalTitle").text("Reset Password");
            $("#authModalSubtitle").html('<i class="fas fa-envelope text-primary me-1"></i>We\'ll send a reset link to your email');
        }
    }
    function resetAuthModal() {
        hideAlert();
        showAllSteps('step1');
        $("#loginForm").removeClass("d-none");
        $("#registerForm").addClass("d-none");
        // Restore register email display paragraph in case it was replaced
        if (!$("#registerEmailDisplay").length) {
            $("#registerForm p.auth-step-label").html(
                'Create account for <strong id="registerEmailDisplay"></strong>' +
                '<a href="#" class="auth-change-link ms-1" id="changeEmailBtn2">Change</a>'
            );
        }
        $("#authEmail, #loginPassword, #regPassword, #regConfirmPassword, #regFirstName, #regLastName, #regPhone, #forgotEmail").val("");
        $("#registerEmailDisplay").text("");
        $("#pwStrengthBar").css({width:"0"});
        $("#pwStrengthText").text("");
        $("#agreeTerms").prop("checked", false);
    }
    function setLoading($btn, loading, text) {
        if (loading) {
            $btn.prop("disabled", true).html('<span class="auth-spinner"></span> Please wait...');
        } else {
            $btn.prop("disabled", false).text(text);
        }
    }

    /* ---- Step 1: email continue — check if email exists, show login or register ---- */
    $("#authContinueBtn").on("click", function () {
        var email = $("#authEmail").val().trim();
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showAlert("Please enter a valid email address.", "error");
            return;
        }
        hideAlert();
        var $btn = $(this);
        setLoading($btn, true, "Continue");

        $.ajax({
            url:      BASE + "auth_handler.php",
            type:     "POST",
            data:     { action: "check_email", email: email },
            dataType: "json",
            success: function (res) {
                setLoading($btn, false, "Continue");
                $("#loginEmailDisplay, #registerEmailDisplay").text(email);
                showAllSteps('step2');

                if (res.exists) {
                    // Existing user — show login
                    $("#loginForm").removeClass("d-none");
                    $("#registerForm").addClass("d-none");
                    $("#authModalTitle").text("Welcome back!");
                    $("#authModalSubtitle").html('<i class="fas fa-check-circle text-success me-1"></i>Sign in to your account');
                    $("#loginPassword").focus();
                } else {
                    // New user — show register directly
                    $("#loginForm").addClass("d-none");
                    $("#registerForm").removeClass("d-none");
                    $("#authModalTitle").text("Create Account");
                    $("#authModalSubtitle").html('<i class="fas fa-check-circle text-success me-1"></i>Join Isa &amp; Dagi Mobile Shop');
                    $("#regFirstName").focus();
                }
            },
            error: function () {
                setLoading($btn, false, "Continue");
                // Fallback: show login form
                $("#loginEmailDisplay, #registerEmailDisplay").text(email);
                showAllSteps('step2');
                $("#authModalTitle").text("Sign In");
                $("#authModalSubtitle").html('<i class="fas fa-check-circle text-success me-1"></i>Your information is protected');
                $("#loginPassword").focus();
            }
        });
    });
    $("#authEmail").on("keypress", function (e) {
        if (e.which === 13) $("#authContinueBtn").trigger("click");
    });

    /* ---- Change email ---- */
    $(document).on("click", "#changeEmailBtn, #changeEmailBtn2", function (e) {
        e.preventDefault();
        hideAlert();
        showAllSteps('step1');
        $("#authEmail").focus();
    });

    /* ---- New customer: go straight to register (Step 1 shortcut) ---- */
    $(document).on("click", "#showRegisterDirect", function (e) {
        e.preventDefault();
        hideAlert();
        var email = $("#authEmail").val().trim();
        // Pre-fill email display if they typed one
        if (email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            $("#loginEmailDisplay, #registerEmailDisplay").text(email);
        } else {
            $("#loginEmailDisplay, #registerEmailDisplay").text("your email");
        }
        showAllSteps('step2');
        $("#loginForm").addClass("d-none");
        $("#registerForm").removeClass("d-none");
        $("#authModalTitle").text("Create Account");
        $("#authModalSubtitle").html('<i class="fas fa-check-circle text-success me-1"></i>Join Isa &amp; Dagi Mobile Shop');
        // If no email yet, focus email field inside register (we'll use authEmail still)
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            // Show email input at top of register form
            $("#registerEmailDisplay").text("").closest("p").html(
                'Register with email: <input type="email" id="regDirectEmail" class="auth-input mt-1" placeholder="Enter your email" autocomplete="email" style="display:inline-block;width:100%;">'
            );
            $("#regDirectEmail").focus();
        } else {
            $("#regFirstName").focus();
        }
    });

    /* ---- Open forgot password inline (from Step 1) ---- */
    $(document).on("click", "#showForgotInline, #forgotPwdLink", function (e) {
        e.preventDefault();
        hideAlert();
        // Pre-fill forgot email from whatever was typed
        var email = $("#authEmail").val().trim() || $("#loginEmailDisplay").text();
        if (email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            $("#forgotEmail").val(email);
        }
        showAllSteps('forgot');
        $("#forgotEmail").focus();
    });

    /* ---- Back to login from forgot ---- */
    $(document).on("click", "#backToLoginFromForgot", function (e) {
        e.preventDefault();
        hideAlert();
        var email = $("#forgotEmail").val().trim();
        if (email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            // Go to login step with this email
            $("#authEmail").val(email);
            $("#loginEmailDisplay, #registerEmailDisplay").text(email);
            showAllSteps('step2');
            $("#loginForm").removeClass("d-none");
            $("#registerForm").addClass("d-none");
            $("#authModalTitle").text("Sign In");
            $("#authModalSubtitle").html('<i class="fas fa-check-circle text-success me-1"></i>Your information is protected');
            $("#loginPassword").focus();
        } else {
            showAllSteps('step1');
            $("#authEmail").focus();
        }
    });

    /* ---- Forgot password submit (inline) ---- */
    $(document).on("click", "#forgotSubmitBtn", function (e) {
        var $btn  = $(this);
        var email = $("#forgotEmail").val().trim();
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showAlert("Please enter a valid email address.", "error");
            return;
        }
        hideAlert();
        $btn.prop("disabled", true).html('<span class="auth-spinner"></span> Sending...');
        $.ajax({
            url:      BASE + "auth_handler.php",
            type:     "POST",
            data:     { action: "forgot_password_inline", email: email },
            dataType: "json",
            success: function (res) {
                $btn.prop("disabled", false).html('<i class="fas fa-paper-plane me-2"></i>Send Reset Link');
                if (res.success) {
                    showAlert(res.message, "success");
                    $btn.prop("disabled", true);
                } else {
                    showAlert(res.message || "Something went wrong. Please try again.", "error");
                }
            },
            error: function () {
                $btn.prop("disabled", false).html('<i class="fas fa-paper-plane me-2"></i>Send Reset Link');
                showAlert("Connection error. Please try again.", "error");
            }
        });
    });
    $(document).on("keypress", "#forgotEmail", function (e) {
        if (e.which === 13) $("#forgotSubmitBtn").trigger("click");
    });

    /* ---- Switch login / register ---- */
    $(document).on("click", "#switchToRegister", function (e) {
        e.preventDefault();
        hideAlert();
        showAllSteps('step2');
        $("#loginForm").addClass("d-none");
        $("#registerForm").removeClass("d-none");
        $("#authModalTitle").text("Create Account");
        $("#authModalSubtitle").html('<i class="fas fa-check-circle text-success me-1"></i>Join Isa &amp; Dagi Mobile Shop');
        $("#regFirstName").focus();
    });
    $(document).on("click", "#switchToLogin", function (e) {
        e.preventDefault();
        hideAlert();
        showAllSteps('step2');
        $("#registerForm").addClass("d-none");
        $("#loginForm").removeClass("d-none");
        $("#authModalTitle").text("Welcome back!");
        $("#authModalSubtitle").html('<i class="fas fa-check-circle text-success me-1"></i>Sign in to your account');
        $("#loginPassword").focus();
    });

    /* ---- Toggle password ---- */
    $("#toggleLoginPwd").on("click", function () {
        var $i = $("#loginPassword");
        $i.attr("type", $i.attr("type") === "password" ? "text" : "password");
        $(this).find("i").toggleClass("fa-eye fa-eye-slash");
    });
    $("#toggleRegPwd").on("click", function () {
        var $i = $("#regPassword");
        $i.attr("type", $i.attr("type") === "password" ? "text" : "password");
        $(this).find("i").toggleClass("fa-eye fa-eye-slash");
    });
    $("#toggleRegConfirmPwd").on("click", function () {
        var $i = $("#regConfirmPassword");
        $i.attr("type", $i.attr("type") === "password" ? "text" : "password");
        $(this).find("i").toggleClass("fa-eye fa-eye-slash");
    });

    /* ---- Password strength meter ---- */
    $("#regPassword").on("input", function () {
        var pw  = $(this).val();
        var str = 0;
        if (pw.length >= 6)           str++;
        if (pw.length >= 10)          str++;
        if (/[A-Z]/.test(pw))         str++;
        if (/[0-9]/.test(pw))         str++;
        if (/[^A-Za-z0-9]/.test(pw))  str++;
        var colors = ["","#dc3545","#fd7e14","#ffc107","#28a745","#20c997"];
        var labels = ["","Weak","Fair","Good","Strong","Very Strong"];
        var pct    = str > 0 ? (str / 5 * 100) + "%" : "0";
        $("#pwStrengthBar").css({width: pct, background: colors[str] || "#eee"});
        $("#pwStrengthText").text(str > 0 ? labels[str] : "").css("color", colors[str] || "#888");
    });

    /* ---- LOGIN submit ---- */
    $("#loginSubmitBtn").on("click", function () {
        var $btn  = $(this);
        var email = $("#authEmail").val().trim();
        var pass  = $("#loginPassword").val();
        if (!pass) { showAlert("Please enter your password.", "error"); return; }
        setLoading($btn, true, "Sign In");
        hideAlert();
        $.ajax({
            url:      BASE + "auth_handler.php",
            type:     "POST",
            data:     { action: "login", email: email, password: pass },
            dataType: "json",
            success: function (res) {
                setLoading($btn, false, "Sign In");
                if (res.success) {
                    showAlert(res.message, "success");
                    setTimeout(function () { location.reload(); }, 900);
                } else {
                    showAlert(res.message, "error");
                }
            },
            error: function () {
                setLoading($btn, false, "Sign In");
                showAlert("Connection error. Please try again.", "error");
            }
        });
    });
    $("#loginPassword").on("keypress", function (e) {
        if (e.which === 13) $("#loginSubmitBtn").trigger("click");
    });

    /* ---- REGISTER submit ---- */
    $("#registerSubmitBtn").on("click", function () {
        var $btn    = $(this);
        // Support both normal flow (email from step1) and direct register flow
        var email   = $("#regDirectEmail").length ? $("#regDirectEmail").val().trim() : $("#authEmail").val().trim();
        var fn      = $("#regFirstName").val().trim();
        var ln      = $("#regLastName").val().trim();
        var phone   = $("#regPhone").val().trim();
        var pass    = $("#regPassword").val();
        var confirm = $("#regConfirmPassword").val();

        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showAlert("Please enter a valid email address.", "error"); return;
        }
        if (!fn)               { showAlert("Please enter your first name.", "error"); return; }
        if (pass.length < 6)   { showAlert("Password must be at least 6 characters.", "error"); return; }
        if (pass !== confirm)  { showAlert("Passwords do not match.", "error"); return; }
        if (!$("#agreeTerms").is(":checked")) { showAlert("Please agree to the Terms of Service.", "error"); return; }

        setLoading($btn, true, "Create Account");
        hideAlert();
        $.ajax({
            url:      BASE + "auth_handler.php",
            type:     "POST",
            data:     { action: "register", email: email, first_name: fn, last_name: ln, phone: phone, password: pass },
            dataType: "json",
            success: function (res) {
                setLoading($btn, false, "Create Account");
                if (res.success) {
                    showAlert(res.message, "success");
                    setTimeout(function () { location.reload(); }, 1100);
                } else {
                    showAlert(res.message, "error");
                }
            },
            error: function () {
                setLoading($btn, false, "Create Account");
                showAlert("Connection error. Please try again.", "error");
            }
        });
    });

    /* ---- Social buttons ---- */
    $(document).on("click", ".auth-social-btn", function (e) {
        e.preventDefault();
        var name = $(this).attr("data-social") || $(this).attr("title") || "Social login";
        showAlert(name + " sign-in coming soon! Please use email for now.", "error");
    });

    /* =====================================================
       SEARCH AUTOCOMPLETE
       ===================================================== */
    var searchTimer;
    $(".nav-search-input").on("input", function () {
        clearTimeout(searchTimer);
        var q = $(this).val().trim();
        if (q.length < 2) { $("#searchSuggestions").remove(); return; }
        searchTimer = setTimeout(function () {
            $.ajax({
                url:      BASE + "Template/ajax.php",
                type:     "POST",
                data:     { search: q },
                dataType: "json",
                success: function (res) {
                    $("#searchSuggestions").remove();
                    if (!res || !res.length) return;
                    var html = '<ul id="searchSuggestions" class="search-suggestions">';
                    $.each(res, function (i, item) {
                        var price = 'USD ' + parseFloat(item.item_price).toFixed(2);
                        html += '<li><a href="' + BASE + 'product.php?item_id=' + item.item_id + '">' +
                                '<img src="' + item.item_image + '" alt="">' +
                                '<span>' + item.item_name + '</span>' +
                                '<strong>' + price + '</strong>' +
                                '</a></li>';
                    });
                    html += '</ul>';
                    $(".nav-search").css("position","relative").append(html);
                }
            });
        }, 300);
    });

    $(document).on("click", function (e) {
        if (!$(e.target).closest(".nav-search").length) {
            $("#searchSuggestions").remove();
        }
    });

});
