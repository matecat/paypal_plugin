
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
        return <div className="preview-info">Current Segment ID: {this.props.currentSid}</div>
    }
}


export default PreviewInfo ;

