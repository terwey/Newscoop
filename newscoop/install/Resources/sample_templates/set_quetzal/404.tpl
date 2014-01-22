{{ config_load file="{{ $gimme->language->english_name }}.conf" }}
{{ include file="_tpl/_html-head.tpl" }}

<body>
<!--[if lt IE 7]>
    <p class="chromeframe">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">activate Google Chrome Frame</a> to improve your experience.</p>
<![endif]-->
          
{{ include file="_tpl/header.tpl" }}

<section role="main" class="internal-page">
    <div class="wrapper">

        {{ include file="_tpl/article-header.tpl" }}

        <div class="container">
            <section id="content">
                <div class="row">

                <div class="span12 article-container">
                    <br>
                    <center>
                        <h4 style="text-align:center;">{{ #notFoundMessage# }}</h4>
                        <br>
                        <a href="javascript:history.back()" class="btn btn-red">← {{ #back# }}</a>
                    </center>
                    <br>
                </div>

                         
                </div> <!--end div class="row"-->      

            </section> <!-- end section id=content -->
        </div> <!-- end div class='container' -->
    </div> <!-- end div class='wrapper' -->
</section> <!-- end section role main -->

{{ include file="_tpl/footer.tpl" }}

{{ include file="_tpl/_html-foot.tpl" }}
