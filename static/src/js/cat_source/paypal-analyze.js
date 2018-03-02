

(function() {

    function overrideAnalyzeHeaderFn(AnalyzeHeader) {
        AnalyzeHeader.prototype.moreProjectInfo = function() {
            let projectType = this.props.project.get('project_type');
            if (projectType) {
                return <div className={"project-type "+ projectType.toLowerCase() +"-type"}>
                    {projectType}
                </div>;
            }
            return "";

        };

        return AnalyzeHeader;
    }

    function overrideJobLinkFn(OpenJobBox) {
        let originalGetUrl = OpenJobBox.prototype.getUrl;
        OpenJobBox.prototype.getUrl = function() {
            let projectType = this.props.project.get('project_type');
            let url = originalGetUrl.apply(this);
            if (projectType && projectType === 'LQA') {
                url = window.location.protocol + '//' + window.location.host +
                    "/plugins/paypal/lqa/" + this.props.outsourceJobId + "/" + this.props.job.get('review_password');
            } else if (projectType && projectType === 'LR') {
                url = url.replace('/translate/' , '/revise/');
            }
            return url;

        };

        OpenJobBox.prototype.openJob = function() {
            let originalOpenJob = OpenJobBox.prototype.openJob;
            let projectType = this.props.project.get('project_type');
            let url = originalGetUrl.apply(this);
            if (projectType && projectType === 'LQA') {
                url = "/plugins/paypal/lqa/" + this.props.outsourceJobId + "/" + this.props.job.get('review_password');
            } else if (projectType && projectType === 'LR') {
                url =  '/revise/'+ this.props.project.get('project_slug')+'/'+ this.props.job.get('source') +'-'+ this.props.job.get('target')+'/'
                    + this.props.outsourceJobId +'-'+ this.props.job.get('review_password')  ;
            }
            return url;

        };

        return AnalyzeHeader;
    }

    overrideAnalyzeHeaderFn(AnalyzeHeader);
    overrideJobLinkFn(OpenJobBox);
})() ;
