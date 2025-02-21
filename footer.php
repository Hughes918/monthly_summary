<?php
// Plain PHP file with standard HTML footer content
?>
<footer id="deos-footer">
    <div id="deos-footer-content">
        <div class="grid-x">
            <p id="deos-footer-info" class="cell large-9">
                Delaware Environmental Observing System | 102 Pearson Hall | University of Delaware | Newark, DE 19716-2541<br>
                Center for Environmental Monitoring &amp; Analysis<br>
                Phone: (302) 831-6906  •   Fax: (302) 831-6654  •  © 2025
            </p>
            <div id="deos-footer-connect" class="cell large-3">
                <a id="deos-connect-facebook" class="deos-social-icon" href="https://www.facebook.com/pages/The-Delaware-Environmental-Observing-System/324846850825?ref=hl" target="_blank"></a>
                <a id="deos-connect-twitter" class="deos-social-icon" href="https://twitter.com/udcema" target="_blank"></a>
                <a id="deos-connect-email" class="deos-social-icon" href="http://deos.udel.edu/about/contact.php"></a>
            </div>
        </div>
        <p id="deos-footer-logos">
            <a href="http://www.udel.edu" target="_blank" title="UD Link">
                <img id="footer-logo-ud" src="assets/footer-logo-ud.png" alt="UD Logo">
            </a>
            <a href="http://www.ceoe.udel.edu/" target="_blank" title="CEOE Link">
                <img id="footer-logo-ceoe" src="assets/footer-logo-ceoe.png" alt="CEOE Logo">
            </a>
        </p>
        <ul id="deos-footer-links">
            <li class="footer-item">© 2025 University of Delaware</li>
            <li class="footer-item">
                <a class="footer-link" href="//www.udel.edu/home/legal-notices/">Legal Notices</a>
            </li>
            <li class="footer-item">
                <a class="footer-link" href="//www.udel.edu/home/legal-notices/accessibility/">Accessibility Notice</a>
            </li>
        </ul>
    </div>
</footer>

<style lang="scss">
    #deos-footer {
        background: #eee;
        border-top: 1px solid #d8d8d8;
        padding: 30px 0 10px;
        font-family: 'OpenSans', sans-serif;
        margin-top: 40px;
    }

    #deos-footer-content {
        width: 920px;
        padding: 0 20px;
        margin: 0 auto;

        @include breakpoint(medium down) {
            width: 100%;
        }
    }

    #deos-footer-connect {
        text-align: right;
        padding-right: 30px;

        @include breakpoint(medium down) {
            text-align: left;
            margin-bottom: 30px;
        }

        .deos-social-icon {
            background: url('assets/deos_social_icons.png') no-repeat;
            display: inline-block;
            height: 30px;
            width: 30px;
            margin: 0 10px;
        }

        #deos-connect-twitter {
            background-position: 0 -80px;
        }

        #deos-connect-email {
            background: url('assets/deos_mail_icon.png') no-repeat;
            background-size: 30px 30px;
        }
    }

    #deos-footer-info {
        line-height: 1.5em;
        font-size: 12px;
        padding-left: 10px;
        margin: 0 0 30px;
    }

    #deos-footer-logos {
        margin: 0;
        padding-left: 10px;

        #footer-logo-ud {
            max-width: 250px;
            width: auto;
            height: 50px;
            filter: grayscale(100%);
        }

        #footer-logo-ceoe {
            width: auto;
            height: 30px;
            padding-left: 10px;
            filter: grayscale(100%);
        }
    }

    #deos-footer-links {
        font-size: 0.7em;
        display: inline-flex;
        list-style-type: none;

        @include breakpoint(small only) {
            padding: 0;
        }

        .footer-item {
            padding: 0 10px;
        }

        .footer-link {
            color: sienna;
            text-decoration: none;
        }
    }

    @media (max-width: 600px) {
        #deos-footer-content {
            width: 100%;
            padding: 0 10px;
        }
        #deos-footer-info {
            font-size: 10px;
            text-align: center;
            padding: 5px 0;
        }
        #deos-footer-connect {
            text-align: center;
            padding: 10px 0;
            margin-bottom: 10px;
        }
        #deos-footer-logos {
            text-align: center;
            padding: 10px 0;
        }
        #deos-footer-links {
            flex-direction: column;
            align-items: center;
        }
        #deos-footer-links .footer-item {
            padding: 5px 0;
        }
    }
</style>