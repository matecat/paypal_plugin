


(function() {
    let originalOverrideButtonsForRevision = UI.overrideButtonsForRevision;
    $.extend(UI, {
        overrideButtonsForRevision: function () {
            var div = $('<ul>' + UI.segmentButtons + '</ul>');

            div.find('.translated').text('APPROVED').removeClass('translated').addClass('approved');
            var nextSegment = UI.currentSegment.next();
            var goToNextApprovedButton = !nextSegment.hasClass('status-translated');
            div.find('.next-untranslated').parent().remove();
            UI.segmentButtons = div.html();
        }
    });

})() ;