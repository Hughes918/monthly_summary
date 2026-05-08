<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DEOS Monthly Summary</title>
    <link rel="stylesheet" href="styles.css">
    <!-- You may include any additional CSS/JS needed -->
</head>
<body>
<header id="deos-header">
    <div id="header">
        <div class="grid-x">
            <div id="header-title" class="cell auto">
                <div id="header-logo">
                    <a href="http://deos.udel.edu">
                        <img id="header-logo-img" src="assets/deos-logo.png" alt="DEOS Logo">
                    </a>
                </div>
                <div id="header-logo-title">
                    <a id="logo-title-link" href="/"><span class="mobile-title-line-1">DELAWARE ENVIRONMENTAL </span><span class="mobile-title-line-2">OBSERVING SYSTEM</span></a>
                </div>
            </div>
            <button id="header-mobile-menu-toggle" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="header-nav">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
        <ul id="header-nav">
            <li class="header-nav-option"><a href="http://deos.udel.edu">Home</a></li>
            <li class="header-nav-option"><a href="http://deos.udel.edu/almanac/">Almanac</a></li>
            <li class="header-nav-option"><a href="http://deos.udel.edu/about/">About</a></li>
            <li class="header-nav-option"><a href="http://deos.udel.edu/data/">Data</a></li>
            <li class="header-nav-option"><a href="http://deos.udel.edu/applications/">Applications</a></li>
            <li class="header-nav-option"><a href="http://climate.udel.edu/delawares-climate/" target="_blank">Climate</a></li>
        </ul>
    </div>
</header>
<script>
    (function () {
        var toggleButton = document.getElementById('header-mobile-menu-toggle');
        var nav = document.getElementById('header-nav');
        if (!toggleButton || !nav) {
            return;
        }

        toggleButton.addEventListener('click', function () {
            var isOpen = nav.classList.toggle('is-open');
            toggleButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            toggleButton.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
        });
    })();
</script>
</body>
</html>

<style lang="scss">
    @import '../assets/css/foundation-components';
    @import '../assets/css/almanac_settings';

    @font-face {
        font-family: 'OpenSans';
        src: url('https://deos.udel.edu/fonts/OpenSans-Regular.eot');
        src: local('Ã¢ËœÂº'), url('https://deos.udel.edu/fonts/OpenSans-Regular.woff') format('woff'), url('https://deos.udel.edu/fonts/OpenSans-Regular.ttf') format('truetype'), url('https://deos.udel.edu/fonts/OpenSans-Regular.svg') format('svg');
        font-weight: normal;
        font-style: normal;
    }

    #deos-header {
        background: #eee;
        border-bottom: 1px solid #000;
    }

    #header {
        max-width: 960px;       // changed from fixed width
        width: 100%;            // use full available width
        height: 133px;
        margin: 0 auto;
        padding: 0 20px;
        position:relative;

        @include breakpoint(medium down) {
            width:100%;
            padding:0 12px;
            height: 80px;
        }

        @include breakpoint(small only) {
            height:60px; // reduced header height for mobile
        }
    }

    #header-title {
        padding:15px 0;

        @include breakpoint(small only) {
            padding:10px 0;
        }
    }

    #header-logo {
        vertical-align:top;
        display:inline-block;
    }

    @include breakpoint(small only) {
        #header-logo, #header-logo-img {
            display: none !important;
        }
    }

    @include breakpoint(medium down) {
        #header-logo-img {
            height:50px;
        }
    }

    #header-logo-title {
        display:inline-block;
        font-size:30px;
        font-weight:bold;
        margin-left:5px;
        position: relative;
        top: 7px;

        @include breakpoint(medium only) {
            font-size: 19px;
            line-height:32px;
        }

        @include breakpoint(small only) {
            font-size:8px;    // smaller font-size on mobile
            line-height:10px;   // adjusted line spacing
            text-align: center;  // center the title
            width: 100%;         // take full width on mobile
            margin-left: 0;      // remove left margin
            position: static;    // remove top offset
        }

        #logo-title-link {
            font-family: 'OpenSans', sans-serif;
            color:#000;
            text-decoration:none;

            @include breakpoint(small only) {
                font-size: 12px;  // smaller text for small screens
            }
        }
    }

    /* Removed #header-mobile-menu styles */

    #header-nav {
        list-style: none;
        line-height: 2em;
        padding: 0;
        margin: 0 auto; // center menu horizontally
        display: inline-block;
        text-align: center;

        @include breakpoint(medium down) {
            position: static;
            margin: 0 auto;
            background: none;
            box-shadow: none;
        }

        .header-nav-option {
            font-size: 1em;
            line-height: 2em;
            font-weight: bold;
            width: auto;      // allow items to size based on content
            text-align: center;
            display: inline-block !important;
            font-family: 'OpenSans', sans-serif;
            padding: 0 10px;  // adjust horizontal spacing
            border-top: none; // remove top borders in mobile

            a {
                color: #818181;
                text-decoration: none;
            }
        }
    }

    #header-nav .header-nav-option a,
    #header-nav .header-nav-option a:link,
    #header-nav .header-nav-option a:visited,
    #header-nav .header-nav-option a:hover,
    #header-nav .header-nav-option a:active {
        color: #818181 !important;
        text-decoration: none;
        font-family: 'OpenSans', sans-serif;
    }

    #logo-title-link .mobile-title-line-1,
    #logo-title-link .mobile-title-line-2 {
        display: inline;
    }

    #header-mobile-menu-toggle {
        display: none;
    }

    @media screen and (max-width: 768px) {
        #header {
            height: auto;
            min-height: 64px;
        }

        #header .grid-x {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 4px;
        }

        #header-title {
            display: flex;
            align-items: center;
            flex: 1 1 auto;
            padding: 8px 0;
            min-width: 0;
        }

        #header-logo-img {
            display: none !important;
        }

        #header-logo-title {
            display: block;
            flex: 1 1 auto;
            width: 100%;
            margin-left: 0;
            text-align: left;
            top: 0;
            position: static;
        }

        #logo-title-link {
            display: -webkit-box !important;
            -webkit-box-orient: vertical !important;
            -webkit-line-clamp: 2 !important;
            overflow: hidden !important;
            font-size: clamp(1.2rem, 5.5vw + 0.45rem, 1.55rem) !important;
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.18 !important;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        #logo-title-link .mobile-title-line-1,
        #logo-title-link .mobile-title-line-2 {
            display: inline !important;
            white-space: normal !important;
        }

        #header-mobile-menu-toggle {
            display: inline-flex;
            flex-direction: column;
            justify-content: center;
            gap: 4px;
            width: 32px;
            height: 32px;
            padding: 5px;
            border: 1px solid #a9a9a9;
            border-radius: 6px;
            background: #fff;
            cursor: pointer;
            flex: 0 0 auto;
        }

        #header-mobile-menu-toggle span {
            display: block;
            width: 100%;
            height: 2px;
            background: #555;
            border-radius: 2px;
        }

        #header-nav {
            display: none;
            width: 100%;
            margin: 4px 0 0;
            padding: 8px 0 2px;
            text-align: left;
        }

        #header-nav.is-open {
            display: block;
        }

        #header-nav .header-nav-option {
            display: block !important;
            padding: 4px 0;
            line-height: 1.7em;
            text-align: left;
        }
    }

    /* Fluid masthead: title stays large, max 2 lines; hide logo when header strip is narrow */
    #header {
        container-type: inline-size;
        container-name: deos-header;
        height: auto !important;
        min-height: 72px;
        padding-top: 12px;
        padding-bottom: 12px;
        box-sizing: border-box;
    }

    @container deos-header (max-width: 680px) {
        #header-logo {
            display: none !important;
        }
    }

    /* Fallback when container queries unsupported */
    @supports not (container-type: inline-size) {
        @media screen and (max-width: 680px) {
            #header-logo {
                display: none !important;
            }
        }
    }

    #header .grid-x {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    #header-title {
        display: flex !important;
        align-items: center;
        gap: 12px;
        min-width: 0;
        flex: 1 1 auto;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
    }

    #header-logo {
        flex-shrink: 0;
    }

    #header-logo-title {
        flex: 1 1 auto;
        min-width: 0;
        margin-left: 0 !important;
        position: static !important;
        top: auto !important;
        font-size: inherit !important;
        font-weight: inherit !important;
        display: block !important;
        width: auto !important;
        text-align: left !important;
    }

    #logo-title-link {
        display: -webkit-box !important;
        -webkit-box-orient: vertical !important;
        -webkit-line-clamp: 2 !important;
        overflow: hidden !important;
        word-break: break-word;
        overflow-wrap: anywhere;
        font-family: 'OpenSans', sans-serif;
        font-size: clamp(1.2rem, 4vw + 0.55rem, 2.05rem) !important;
        font-weight: 700 !important;
        line-height: 1.16 !important;
        letter-spacing: -0.02em;
        color: #000 !important;
        text-decoration: none !important;
    }

    #logo-title-link .mobile-title-line-1,
    #logo-title-link .mobile-title-line-2 {
        display: inline !important;
        white-space: normal !important;
    }
</style>