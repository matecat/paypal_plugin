
class PreviewInfo extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
        };

    }

    componentDidMount() {}

    componentWillUnmount() {}

    componentDidUpdate() {}

    render() {
        return <div className="preview-info-container">
            <h2>Segment info</h2>
            <div className="preview-info">Current Segment ID: {this.props.currentSid}</div>
            <div className="preview-info">Total previews: {this.props.segmentPreviews.size}</div>
            <div className="preview-info">Current preview: {this.props.currentPreview}</div>
        </div>
    }
}


export default PreviewInfo ;

