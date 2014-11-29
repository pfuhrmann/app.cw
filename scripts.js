// Validate form
// checkRun = global
function validate(event, url) {
    if (checkRun === true) {
        return;
    }
    event.preventDefault();

    var data = $("form").serialize();
    $.post("index.php?uri="+url, data, function() {})
        .done(function(res) {
            var errorsEl = $("#errors");
            errorsEl.html("");
            errorsEl.addClass("hidden");

            // Show errors
            if (res.length !== 2) {
                var errorsAll = $.parseJSON(res);

                errorsEl.removeClass("hidden");
                errorsEl.append("<ul>");
                $.each(errorsAll, function (index, errors) {
                    $.each(errors, function (index, error) {
                        errorsEl.append("<li>"+error+"</li>");
                    });
                });
                errorsEl.append("</ul>");
            } else {
                checkRun = true;
                $("#submit").trigger('click');
            }
        })
};