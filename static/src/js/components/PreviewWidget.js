let Actions = require('./../actions/PreviewActions');


class PreviewWidget extends React.Component {

    constructor(props) {
        super(props);
        this.isMac = (navigator.platform == 'MacIntel')? true : false;
        this.width = '150px';
        this.height = '200px';
    }

    getPreviewPoint() {
        let self = this;
        let segmentInfo = this.props.segmentsInfo.find(function (segment, i) {
            if (segment.get('segment') === parseInt(self.props.currentSid)) {
                return true;
            }
        });
        return segmentInfo.get('previews').find(function (preview) {
            return preview.get('file_index') === self.props.currentPreview;
        });
    }

    calculateStyle() {
        let preview = this.getPreviewPoint();
        if (this.props.imageWidth === preview.get('fileW')){
            return  {
                width: this.width,
                height: this.height,
                left: preview.get('x')  + preview.get('w') + 20 + 'px',
                top: preview.get('y') + 'px',
            };
        } else {
            let image_height = (this.props.imageWidth/preview.get('fileW')) * preview.get('fileH');
            let left = parseInt((preview.get('x')/preview.get('fileW')) * this.props.imageWidth) + preview.get('w') + 20;
            let top = parseInt((preview.get('y')/preview.get('fileH')) * image_height) ;
            return  {
                width: this.width,
                height: this.height,
                left: left + 'px',
                top: top + 'px'
            };
        }
    }

    componentDidMount() {}

    componentWillUnmount() {}

    componentDidUpdate() {}

    render() {
        let highlighterStyle = this.calculateStyle();
        return <div
            className={"preview-widget"}
            style={highlighterStyle}
        />

    }
}


export default PreviewWidget ;

