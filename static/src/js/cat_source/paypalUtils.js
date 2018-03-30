let paypalUtils = {
    getPreviewData: function () {
        return $.ajax({
            async: true,
            type: "get",
            url : "/plugins/paypal/preview/" + config.id_job + "/" + config.password
        });
    },
    checkReferenceFiles: function (  ) {
        var path = sprintf(
            '/plugins/paypal/reference-files/%s/%s/list',
            config.id_job, config.password
        );
        return $.ajax({
            type: "GET",
            url : path
        });
    },
    getSegmentsPreviewInfo: function ( segments ) {
        return $.ajax({
            async: true,
            type: "get",
            url : "/plugins/paypal/job/"+ config.id_job + "/"+ config.password + "/segments/" + segments.join()
        });
    },
    getJobInstructions: function (  ) {
        return $.ajax({
            async: true,
            type: "get",
            url : "/plugins/paypal/job/"+ config.id_job + "/"+ config.password + "/instructions"
        });
    }
};

module.exports = paypalUtils;