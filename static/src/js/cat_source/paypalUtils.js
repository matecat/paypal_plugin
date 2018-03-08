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
        var data = {
            segments_id: segments
        };
        return $.ajax({
            async: true,
            data: data,
            type: "get",
            url : "/plugins/paypal/job/"+ config.id_job + "/"+ config.password + "/segments"
        });
    },
    getJobInstructions: function (  ) {
        return $.ajax({
            async: true,
            type: "get",
            url : "/plugins/paypal/job/"+ config.id_job + "/"+ config.password + "/instructions"
        });
    },
    approveSegments: function ( segments ) {
        var data = {
            segments_id: segments,
            status: 'approved'
        };
        return $.ajax({
            async: true,
            data: data,
            type: "post",
            url : "/plugins/paypal/job/"+ config.id_job + "/"+ config.password + "/segments/status"
        });
    }
};

module.exports = paypalUtils;