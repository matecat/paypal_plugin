

(function() {

    function overrideJobMenu(JobMenu) {
        JobMenu.prototype.getMoreLinks = function() {
            let projectType = this.props.project.get('project_type');
            if (projectType && projectType === 'LQA') {
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
    overrideProjectContainerFn(ProjectContainer)
})() ;
