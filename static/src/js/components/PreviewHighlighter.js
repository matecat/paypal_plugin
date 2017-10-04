let Actions = require('./../actions/PreviewActions');


class PreviewHighlighter extends React.Component {

    constructor(props) {
        super(props);
    }

    selectSegment() {
        Actions.selectSegment(this.props.segmentInfo.get('segment'), this.props.currentPreview);
    }

    getPreviewPoint() {
        let self = this;
        return this.props.segmentInfo.get('previews').find(function (preview) {
            return preview.get('file_index') === self.props.currentPreview;
        });
    }

    calculateStyle() {
        let preview = this.getPreviewPoint();
        if (this.props.imageWidth === preview.get('file_w')){
            return  {
                width: preview.get('w') + 'px',
                height: preview.get('h') + 'px',
                left: preview.get('x') + 'px',
                top: preview.get('y') + 'px',
            };
        } else {
            let image_height = (this.props.imageWidth/preview.get('file_w')) * preview.get('file_h');

            let width = parseInt((preview.get('w')/preview.get('file_w')) * this.props.imageWidth);
            let height = parseInt((preview.get('h')/preview.get('file_h')) * image_height);
            let left = parseInt((preview.get('x')/preview.get('file_w')) * this.props.imageWidth);
            let top = parseInt((preview.get('y')/preview.get('file_h')) * image_height);
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

    componentDidUpdate() {}

    render() {
        let classActive = (this.props.segmentInfo.get('segment') === parseInt(this.props.currentId)) ? 'active' : '';
        let highlighterStyle = this.calculateStyle();
        // let preview = this.getPreviewPoint();
        // let highlighterStyle = {
        //     width: preview.get('w') + 'px',
        //     height: preview.get('h') + 'px',
        //     left: preview.get('x') + 'px',
        //     top: preview.get('y') + 'px'
        // };
        return <div
        className={"preview-highlighter " + classActive}
        style={highlighterStyle}
        onClick={this.selectSegment.bind(this)}
    />

    }
}


export default PreviewHighlighter ;

