(function() {
    var originalCreateButtons = UI.createButtons;
    var originalSetEvents = UI.setEvents;


    $.extend(UI, {
        setEvents: function () {
            originalSetEvents.apply(this);
            $(document).on('click', '.open-screenshot-button', function () {
                window.open('/preview', "https://www.w3schools.com", "_blank", "toolbar=no,scrollbars=yes,resizable=no,top=500,left=500,width=400,height=400");
            })
        },
        createButtons: function() {
            originalCreateButtons.apply(this);
            var buttonsOb = $('#segment-' + this.currentSegmentId + '-buttons');
            var button = '<li><a class="open-screenshot-button">Open</a></li>';
            buttonsOb.append(button);
        },

    });

})() ;