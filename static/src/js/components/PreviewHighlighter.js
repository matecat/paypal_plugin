let Actions = require('./../actions/PreviewActions');


class PreviewHighlighter extends React.Component {

    constructor(props) {
        super(props);
    }

    selectSegmentClick(e) {
        e.preventDefault();
        e.stopPropagation();
        if (this.props.segmentInfo.get('segment') !==  parseInt(this.props.currentId) ) {
            Actions.selectSegment(this.props.segmentInfo.get('segment'), this.props.currentPreview);
        }
    }

    getPreviewPoint() {
        let self = this;
        return this.props.segmentInfo.get('previews').find(function (preview) {
            return preview.get('file_index') === self.props.currentPreview;
        });
    }

    calculateStyle() {
        let preview = this.getPreviewPoint();
        let scrollSize = 0;
        if (!UI.isMac) {
            scrollSize = 5;
        }
        if (this.props.imageWidth === preview.get('file_w')){
            return  {
                width: preview.get('w') - scrollSize + 'px',
                height: preview.get('h') - scrollSize + 'px',
                left: preview.get('x') - scrollSize + 'px',
                top: preview.get('y') + 'px',
            };
        } else {
            let image_height = (this.props.imageWidth/preview.get('file_w')) * preview.get('file_h');

            let width = parseInt((preview.get('w')/preview.get('file_w')) * this.props.imageWidth) - scrollSize;
            let height = parseInt((preview.get('h')/preview.get('file_h')) * image_height) - scrollSize;
            let left = parseInt((preview.get('x')/preview.get('file_w')) * this.props.imageWidth) - scrollSize;
            let top = parseInt((preview.get('y')/preview.get('file_h')) * image_height) ;
            return  {
                width: width + 'px',
                height: height + 'px',
                left: left + 'px',
                top: top + 'px'
            };
        }
    }

    componentDidMount() {
    }

    componentWillUnmount() {
    }

    shouldComponentUpdate(nextProps, nextState) {
        return true
    }

    componentDidUpdate() {
        if (this.props.segmentInfo.get('segment') === parseInt(this.props.currentId)) {
            $('#plugin-mount-point .preview-image-container').scrollTop(this.elem.offsetTop - 50)
        }
    }

    render() {
        let classActive = (this.props.segmentInfo.get('segment') === parseInt(this.props.currentId)) ? 'active' : '';
        let highlighterStyle = this.calculateStyle();
        return <div
        className={"preview-highlighter " + classActive}
        style={highlighterStyle}
        onClick={this.selectSegmentClick.bind(this)}
        ref={(elem)=> this.elem=elem}
    />

    }
}


export default PreviewHighlighter ;

