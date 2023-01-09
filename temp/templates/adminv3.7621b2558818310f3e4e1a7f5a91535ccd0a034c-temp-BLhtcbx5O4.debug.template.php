<!doctype  html><html><head><meta  name="viewport"  content="width=device-width" /><meta  http-equiv="Content-Type"  content="text/html; charset=UTF-8" /><meta  name="format-detection"  content="telephone=no" /><style> img {
            border: none;
            -ms-interpolation-mode: bicubic;
            max-width: 100%;
        } body {
            background-color: #f6f6f6;
            font: 300 14px/18px 'Lucida Grande', Lucida Sans, Lucida Sans Unicode, sans-serif, Arial, Helvetica, Verdana, sans-serif !important;
            -webkit-font-smoothing: antialiased;
            font-size: 14px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
        } table {
            border-collapse: separate;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
            width: 100%;
        } table td {
            font-family: 'Lucida Grande', Lucida Sans, Lucida Sans Unicode, sans-serif, Arial, Helvetica, Verdana, sans-serif;
            font-size: 14px;
            vertical-align: top;
        } .body {
            background-color: #f6f6f6;
            width: 100%;
        } .container {
            display: block;
            margin: 0 auto !important;
            max-width: 580px;
            padding: 10px;
            width: 580px;
        } .content {
            box-sizing: border-box;
            display: block;
            margin: 0 auto;
            max-width: 580px;
            padding: 10px;
        } .main {
            background: #fff;
            border-radius: 3px;
            width: 100%;
        } .wrapper {
            box-sizing: border-box;
            padding: 20px;
        } .footer {
            clear: both;
            padding-top: 10px;
            text-align: center;
            width: 100%;
        } h1,
        h2,
        h3,
        h4 {
            color: #000000;
            font-family: sans-serif;
            font-weight: 400;
            line-height: 1.4;
            margin: 0;
            margin-bottom: 30px;
        } h1 {
            font-size: 35px;
            font-weight: 300;
            text-align: center;
            text-transform: capitalize;
        } p,
        ul,
        ol {
            font-family: sans-serif;
            font-size: 14px;
            font-weight: normal;
            margin: 0;
            Margin-bottom: 15px;
        } p li,
        ul li,
        ol li {
            list-style-position: inside;
            margin-left: 5px;
        } a {
            color: #3498db;
            text-decoration: underline;
        } .powered-by a {
            text-decoration: none;
        } hr {
            border: 0;
            border-bottom: 1px solid #f6f6f6;
            margin: 20px 0;
        } @media only screen and (max-width: 620px) {
            table[class=body] h1 {
                font-size: 28px !important;
                margin-bottom: 10px !important;
            }

            table[class=body] p,
            table[class=body] ul,
            table[class=body] ol,
            table[class=body] td,
            table[class=body] span,
            table[class=body] a {
                font-size: 16px !important;
            }

            table[class=body] .wrapper,
            table[class=body] .article {
                padding: 10px !important;
            }

            table[class=body] .content {
                padding: 0 !important;
            }

            table[class=body] .container {
                padding: 0 !important;
                width: 100% !important;
            }

            table[class=body] .main {
                border-left-width: 0 !important;
                border-radius: 0 !important;
                border-right-width: 0 !important;
            }

            table[class=body] .btn table {
                width: 100% !important;
            }

            table[class=body] .btn a {
                width: 100% !important;
            }

            table[class=body] .img-responsive {
                height: auto !important;
                max-width: 100% !important;
                width: auto !important;
            }
        } @media only screen and (max-width: 479px) {
            .td_align {
                display: block;
                width: 100%;
            }

            .table_align {
                display: block;
                width: 100%;
            }
        } th {
            width: 100%;
        } @media only screen and (min-width: 621px) {
            td .half-column {
                width: 49%;
                display: inline-block;
                border-right: 1px solid #dedede;
            }

            td .half-column:last-child {
                border-right: none;
            }
        } @media (min-width: 621px) {
            .onlymobile {
                display: none;
            }

            .desktoppadding {
                padding: 0px 13px;
            }
        } @media (max-width: 622px) {
            .notonmobile {
                display: none;
            }

            .mobileblock {
                display: block;
            }

            .mobilemargin {
                margin: 10px 0px;
            }
        }</style></head><body  class=""><table  border="0"  cellpadding="0"  cellspacing="0"  class="body"><tr><td> </td><td  class="container"><div  class="content"><table  class="main"><tr><td  class="wrapper"><table  border="0"  style="width:100%"><tr><th  style="text-align: left;width:50%"  align="left"  valign="top"><img  width="153px"  height="66px"  alt="T@"  src="https://testi.at/templates/testi.png"  style="display:inline-block;height:66px;width:153px;right:0px;" /></th> <td  style="width:50%;text-align:right;font-size:12px"></td> </tr></table> <table  border="0"  cellpadding="0"  cellspacing="0"><tr><td><p>Dobrý den <?php echo $model["user_name"]; ?>,</p><p>Váš účet byl úspěšně vytvořen na emailovou adresu <?php echo $model["user_email"]; ?>.</p><p>Pro přihlášení využíjte odkaz níže.</p><div  style="text-align: center;"><a <?php if((is_bool(  $model['url'] ) && (  $model['url'] )) || !is_bool(  $model['url'] )) { echo " href=\"" . ($model['url']) . "\""; } ?>  style="background: #2196f3;color: white;font-weight: bold;padding:12px;text-decoration:none;border-radius:2px;margin-bottom: 5px;display:inline-block;">Aktivovat účet</a> </div> <br /><p>Pokud odkaz nefunguje využíjte tento odkaz</p><div  style="border: 1px solid #f1f1f1;padding: 8px 15px;background: #f1f1f1;font-size: 11px;"><?php echo $model['url']; ?></div> <br /><p>Nebo můžete zadat aktivační kód</p><div  style="border: 1px solid #f1f1f1;padding: 8px 15px;background: #f1f1f1;font-size: 11px;"><?php echo $model['key']; ?></div> <br /><p  style="font-size:12px !important;"> Děkujeme vám za vaší registraci</p> </td></tr></table> </td> </tr></table> </div> </td> <td> </td></tr></table> </body> </html>