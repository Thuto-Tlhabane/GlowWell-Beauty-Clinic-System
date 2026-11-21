document.addEventListener("DOMContentLoaded", function () {

    const forms = document.querySelectorAll("form");

    forms.forEach(function (form) {

        form.addEventListener("submit", function (event) {

            const requiredFields = form.querySelectorAll("[required]");
            let valid = true;

            requiredFields.forEach(function (field) {

                if (field.value.trim() === "") {
                    field.style.borderColor = "#c0395f";
                    valid = false;
                } else {
                    field.style.borderColor = "";
                }

            });

            if (!valid) {
                event.preventDefault();

                alert("Please complete all required fields before submitting the form.");
            }

        });

    });

});