
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
            <div className="preview-info">Current segment: {this.props.currentSid}</div>
            <div className="preview-info">Total screenshots: {this.props.segmentPreviews.size}</div>
            <div className="preview-info">Current screenshot: {this.props.currentPreview}</div>
        </div>
    }
}


export default PreviewInfo ;

