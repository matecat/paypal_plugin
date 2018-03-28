

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
            } else {
                let project_status = this.props.project.get('project_status');
                if ( !projectType &&  project_status) {
                    let self = this;
                    let translateJobStatus = project_status.get('translate').find(function ( item ) {
                        return parseInt(item.get('id')) === parseInt(self.props.outsourceJobId);
                    });
                    if ( translateJobStatus && translateJobStatus.get('completed') ) {
                        url =  window.location.protocol + '//' + window.location.host + '/revise/'+ this.props.project.get('project_slug')+'/'+ this.props.job.get('source') +'-'+ this.props.job.get('target')+'/'
                            + this.props.outsourceJobId +'-'+ this.props.job.get('review_password')  ;
                    }
                }
            }
            return url;

        };

        let originalOpenJob = OpenJobBox.prototype.openJob;
        OpenJobBox.prototype.openJob = function() {
            let projectType = this.props.project.get('project_type');
            let url = originalOpenJob.apply(this);
            if (projectType && projectType === 'LQA') {
                url = "/plugins/paypal/lqa/" + this.props.outsourceJobId + "/" + this.props.job.get('review_password');
            } else if (projectType && projectType === 'LR') {
                url =  '/revise/'+ this.props.project.get('project_slug')+'/'+ this.props.job.get('source') +'-'+ this.props.job.get('target')+'/'
                    + this.props.outsourceJobId +'-'+ this.props.job.get('review_password')  ;
            } else {
                let project_status = this.props.project.get('project_status');
                if ( !projectType &&  project_status) {
                    let self = this;
                    let translateJobStatus = project_status.get('translate').find(function ( item ) {
                        return parseInt(item.get('id')) === parseInt(self.props.outsourceJobId);
                    });
                    if ( translateJobStatus && translateJobStatus.get('completed') ) {
                        url =  '/revise/'+ this.props.project.get('project_slug')+'/'+ this.props.job.get('source') +'-'+ this.props.job.get('target')+'/'
                            + this.props.outsourceJobId +'-'+ this.props.job.get('review_password')  ;
                    }
                }
            }
            return url;

        };

        return OpenJobBox;
    }

    function overrideJobOpenButton(AnalyzeChunksResume) {
        let original_getOpenButton = AnalyzeChunksResume.prototype.getOpenButton;
        AnalyzeChunksResume.prototype.getOpenButton = function (chunkJob, index ) {
            let project_type = this.props.project.get('project_type');
            let project_status = this.props.project.get('project_status');
            if ( !project_type &&  project_status) {
                let translateJobStatus = project_status.get('translate').find(function ( item ) {
                    return parseInt(item.get('id')) === parseInt(chunkJob.get('id'));
                });
                if ( translateJobStatus && translateJobStatus.get('completed') ) {
                    return <div className="open-revise ui green button open"
                                onClick={this.openOutsourceModal.bind(this, index)}>Revise</div>;
                }
            }
            return original_getOpenButton.apply(this, [chunkJob, index])
        }
    }

    overrideAnalyzeHeaderFn(AnalyzeHeader);
    overrideJobLinkFn(OpenJobBox);
    overrideJobOpenButton(AnalyzeChunksResume);

    var original_renderAnalysisPage = UI.renderAnalysisPage;

    $.extend(UI, {
        renderAnalysisPage: function (  ) {
            original_renderAnalysisPage.apply(this);
            API.PROJECTS.getCompletionStatus().done(function ( data ) {
                UI.currentOutsourceProject.project_status = data.project_status;
                AnalyzeActions.updateProject(UI.currentOutsourceProject);
            })
        }
    });
})() ;
