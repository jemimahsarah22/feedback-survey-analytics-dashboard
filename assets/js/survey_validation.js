document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("surveyForm");

    if (!form) {
        return;
    }

    form.addEventListener("submit", function (event) {

        const requiredQuestions =
            form.querySelectorAll("[data-required='true']");

        let valid = true;

        for (const question of requiredQuestions) {

            const questionId = question.dataset.questionId;
            const type = question.dataset.type;

            if (type === "text") {

                const field =
                    form.querySelector(
                        `[name="answers[${questionId}]"]`
                    );

                if (!field || field.value.trim() === "") {
                    valid = false;
                    break;
                }

            } else if (
                type === "rating" ||
                type === "yes_no" ||
                type === "multiple_choice"
            ) {

                const selected =
                    form.querySelector(
                        `[name="answers[${questionId}]"]:checked`
                    );

                if (!selected) {
                    valid = false;
                    break;
                }
            }
        }

        if (!valid) {

            event.preventDefault();

            alert(
                "Please answer all required questions before submitting the survey."
            );
        }

    });

});