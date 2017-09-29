let Actions = require('./../actions/PreviewActions');


class PreviewActions extends React.Component {

    constructor(props) {
        super(props);
    }

    getCurrentSegment() {
        let self = this;
        return this.props.segmentsInfo.find(function (segment) {
            return segment.get('segment') === parseInt(self.props.currentSid)
        });
    }

    goToNextSegmentImage() {
        // Actions.selectSegment(this.props.segmentInfo.get('segment'), this.props.currentPreview);
    }

    componentDidMount() {
    }

    componentWillUnmount() {
    }

    componentDidUpdate() {}

    render() {
        // let segment = this.getCurrentSegment();
        // let previews = this.props.previews.find()
        return <div>
            {/*{ segment.get('previews').size > 1 ? (*/}
                {/*<div className="preview-button"*/}
                     {/*onClick={this.goToNextSegmentImage.bind(this)}>Next Segment Preview</div>*/}
            {/*) : (null)*/}
            {/*}*/}

            {/*{ this.props.previews.get(this.state.currentPreview).size > 1 ? (*/}
                {/*<div className="preview-button">Next Preview Image</div>*/}
            {/*) : (null)*/}
            {/*}*/}


            {/*<div className="preview-button">Next Preview</div>*/}
        </div>

    }
}


export default PreviewActions ;

