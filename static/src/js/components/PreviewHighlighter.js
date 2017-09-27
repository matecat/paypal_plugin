let Actions = require('./../actions/PreviewActions');


class PreviewHighlighter extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
        };

    }

    selectSegment() {
        let preview = this.props.segmentInfo.get('previews').first().get('file_index');
        Actions.selectSegment(this.props.segmentInfo.get('segment'), preview);
    }

    componentDidMount() {}

    componentWillUnmount() {}

    shouldComponentUpdate(nextProps, nextState) {
        return true
    }

    componentDidUpdate() {}

    render() {
        let classActive = (this.props.segmentInfo.get('segment') === parseInt(this.props.currentId)) ? 'active' : '';
        let highlighterStyle = {
            width: this.props.segmentInfo.get('previews').first().get('w') + 'px',
            height: this.props.segmentInfo.get('previews').first().get('h') + 'px',
            left: this.props.segmentInfo.get('previews').first().get('x') + 'px',
            top: this.props.segmentInfo.get('previews').first().get('y') + 'px',
        };
        return <div
            className={"preview-highlighter " + classActive}
            style={highlighterStyle}
            onClick={this.selectSegment.bind(this)}
        />

    }
}


export default PreviewHighlighter ;

