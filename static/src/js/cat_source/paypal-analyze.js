

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

    overrideAnalyzeHeaderFn(AnalyzeHeader)
})() ;
