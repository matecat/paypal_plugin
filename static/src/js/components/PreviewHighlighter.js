let Actions = require('./../actions/PreviewActions');


class PreviewHighlighter extends React.Component {

    constructor(props) {
        super(props);
        this.isMac = (navigator.platform == 'MacIntel')? true : false;
        this.approvedStatus = "APPROVED";
    }

    selectSegmentClick(e) {
        e.preventDefault();
        e.stopPropagation();
        if (this.props.segmentInfo.get('segment') !==  parseInt(this.props.currentId) ) {
            Actions.selectSegment(this.props.segmentInfo.get('segment'), this.props.currentPreview);
        }
        Actions.openSegment(this.props.currentId);
    }

    getPreviewPoint() {
        let self = this;
        return this.props.segmentInfo.get('previews').find(function (preview) {
            return preview.get('file_index') === self.props.currentPreview;
        });
    }

    calculateStyle() {
        let preview = this.getPreviewPoint();
        if (this.props.imageWidth === preview.get('fileW')){
            return  {
                width: preview.get('w') + 'px',
                height: preview.get('h')  + 'px',
                left: preview.get('x')  + 'px',
                top: preview.get('y') + 'px',
            };
        } else {
            let image_height = (this.props.imageWidth/preview.get('fileW')) * preview.get('fileH');

            let width = parseInt((preview.get('w')/preview.get('fileW')) * this.props.imageWidth) ;
            let height = parseInt((preview.get('h')/preview.get('fileH')) * image_height) ;
            let left = parseInt((preview.get('x')/preview.get('fileW')) * this.props.imageWidth) ;
            let top = parseInt((preview.get('y')/preview.get('fileH')) * image_height) ;
            return  {
                width: width + 'px',
                height: height + 'px',
                left: left + 'px',
                top: top + 'px'
            };
        }
    }

    componentDidMount() {
        let self = this;
        if (this.props.segmentInfo.get('segment') === parseInt(this.props.currentId)) {
            setTimeout(function () {
                $('#plugin-mount-point .preview-image-container').scrollTop(self.elem.offsetTop - 50)
            }, 200)
        }
    }

    componentWillUnmount() {
    }

    shouldComponentUpdate(nextProps, nextState) {
        return true
    }

    componentDidUpdate() {
        let self = this;
        if (this.props.segmentInfo.get('segment') === parseInt(this.props.currentId)) {
            setTimeout(function () {
                $('#plugin-mount-point .preview-image-container').scrollTop(self.elem.offsetTop - 50)
            }, 200)
        }
    }

    render() {
        let cs = classnames({
            'preview-highlighter': true,
            'active' : (this.props.segmentInfo.get('segment') === parseInt(this.props.currentId)),
            'approved-screenshot' : (this.props.segmentInfo.get('status') === this.approvedStatus),
            'dashed-screenshot': (this.props.segmentInfo.get('status') !== this.approvedStatus)
        });
        let thereAreIssues = (this.props.segmentInfo && this.props.segmentInfo.get('issues') && this.props.segmentInfo.get('issues').size > 0);
        let highlighterStyle = this.calculateStyle();
        return <div
        className={cs}
        style={highlighterStyle}
        onClick={this.selectSegmentClick.bind(this)}
        ref={(elem)=> this.elem=elem}
        >
            {(thereAreIssues ) ? (
                <div className="issue-screenshot">
                    <i className="icon-error_outline icon"></i>
                </div>
            ) : (null)}
        </div>

    }
}


export default PreviewHighlighter ;

