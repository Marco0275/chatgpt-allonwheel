// JavaScript Document
document.addEventListener(

    "DOMContentLoaded",

    function () {

        document
            .getElementById("generateArticle")
            .addEventListener(

                "click",

                function () {

                    const form = document.getElementById("aiArticleForm");

                    const data = new FormData(form);

                    fetch(

                        "../ajax/ai_generate_article.php",

                        {

                            method: "POST",

                            body: data

                        }

                    )

                    .then(r => r.json())

                    .then(result => {

                        if (!result.success) {

                            alert(result.error);

                            return;

                        }

                        window.location.href =
                            "article_edit.php?id=" +
                            result.id;

                    });

                }

            );

    }

);