

(function() {

    function overrideJobMenu(JobMenu) {
        JobMenu.prototype.getMoreLinks = function() {
            let projectType = this.props.project.get('project_type');
            if ( (projectType && projectType === 'LQA') || _.isUndefined(projectType)) {
                let lqaUrl = "/plugins/paypal/lqa/" + this.props.jobId + "/" + this.props.review_password;
                return <a className="item" target="_blank" href={lqaUrl}><i className="icon-edit icon"/> LQA</a>;
            }
            return "";

        };
        let orig_reviseMenuLink = JobMenu.prototype.getReviseMenuLink;
        JobMenu.prototype.getReviseMenuLink = function() {
            let projectType = this.props.project.get('project_type');
            if ( projectType && ( projectType === 'TR' || projectType === 'LQA' ) ) {
                return "";
            } else {
                return orig_reviseMenuLink.apply(this);
            }
        };

        return JobMenu;
    }

    function overrideJobContainer(JobContainer) {
        JobContainer.prototype.getTranslateUrl = function() {
            let projectType = this.props.project.get('project_type');
            let use_prefix = ( this.props.jobsLenght > 1 );
            let chunk_id = this.props.job.get('id') + ( ( use_prefix ) ? '-' + this.props.index : '' ) ;
            let possibly_different_review_password = ( this.props.job.has('review_password') ?
                    this.props.job.get('review_password') :
                    this.props.job.get('password')
            );
            return "/plugins/paypal/lqa/" + chunk_id + "/" + possibly_different_review_password;
        };

        return ProjectContainer;
    }
    function overrideProjectContainerFn(ProjectContainer) {
        ProjectContainer.prototype.moreProjectInfo = function() {
            let projectType = this.props.project.get('project_type');
            if (projectType) {
                return <div className={"project-type "+ projectType.toLowerCase() +"-type"}>
                    {projectType}
                </div>;
            }
            return "";

        };

        return ProjectContainer;
    }
    overrideJobMenu(JobMenu);
    overrideJobContainer(JobContainer);
    overrideProjectContainerFn(ProjectContainer)
})() ;
