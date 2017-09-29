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
        let self = this;
        let index = this.props.segmentPreviews.findIndex(function (item) {
            return item.get('file_index') === self.props.currentPreview;
        });
        let next;
        if (this.props.segmentPreviews.get(index+1)) {
            next = index+1;
        } else {
            next = 0;
        }

        Actions.selectSegment(this.props.currentSid, this.props.segmentPreviews.get(next).get('file_index'));
    }

    goToPreviousSegmentImage() {
        let self = this;
        let index = this.props.segmentPreviews.findIndex(function (item) {
            return item.get('file_index') === self.props.currentPreview;
        });
        let next;
        if (this.props.segmentPreviews.get(index - 1)) {
            next = index - 1;
        } else {
            next = this.props.segmentPreviews.size - 1;
        }

        Actions.selectSegment(this.props.currentSid, this.props.segmentPreviews.get(next).get('file_index'));
    }

    // nextImage() {
    //     let index = this.props.preview.findIndex(function (item) {
    //         return item.get('file_index') === self.props.currentPreview;
    //     });
    // }

    componentDidMount() {
    }

    componentWillUnmount() {
    }

    componentDidUpdate() {}

    render() {

        return <div className="preview-actions-container">

            {/*<div className="preview-actions-image">*/}
                {/*<button className="preview-button previous ui left floated blue button"*/}
                        {/*onClick={this.previousImage.bind(this)}*/}
                {/*>Previous Preview</button>*/}
                {/*<button className="preview-button previous ui right floated blue button"*/}
                        {/*onClick={this.nextImage.bind(this)}*/}
                {/*>Next Preview</button>*/}
            {/*</div>*/}

            { this.props.segmentPreviews.size > 1 ? (
                <div className="preview-actions-segment">
                    <button className="preview-button previous ui left floated green button"
                         onClick={this.goToPreviousSegmentImage.bind(this)}>Previous Segment Preview</button>
                    <button className="preview-button next ui right floated green button"
                    onClick={this.goToNextSegmentImage.bind(this)}>Next Segment Preview</button>
                </div>
            ) : (null)
            }



        </div>

    }
}


export default PreviewActions ;

