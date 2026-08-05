// JavaScript Document
document.addEventListener("DOMContentLoaded", () => {

    const articleId = new URLSearchParams(window.location.search).get("id");

    function execute(action) {

        const payload = {

            id: articleId,

            title: document.getElementById("title").value,

            excerpt: document.getElementById("excerpt").value,

            body: document.getElementById("body").value

        };

        fetch("../ajax/ai_" + action + ".php", {

            method: "POST",

            headers: {

                "Content-Type": "application/json"

            },

            body: JSON.stringify(payload)

        })

        .then(r => r.json())

        .then(data => {

            if (!data.success) {

                alert(data.error);

                return;

            }

            if (data.title !== undefined) {

                document.getElementById("title").value = data.title;

            }

            if (data.excerpt !== undefined) {

                document.getElementById("excerpt").value = data.excerpt;

            }

            if (data.body !== undefined) {

                document.getElementById("body").value = data.body;

            }

        });

    }

    document.getElementById("rewrite").onclick = e => {

        e.preventDefault();

        execute("rewrite");

    };

    document.getElementById("improve").onclick = e => {

        e.preventDefault();

        execute("improve");

    };

    document.getElementById("seo").onclick = e => {

        e.preventDefault();

        execute("seo");

    };

    document.getElementById("translate").onclick = e => {

        e.preventDefault();

        execute("translate");

    };

    document.getElementById("publish").onclick = e => {

        e.preventDefault();

        window.location.href =
            "article_publish.php?id=" + articleId;

    };

});