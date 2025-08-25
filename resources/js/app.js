import "./bootstrap";
import {
    mount as mountNewsChart,
    unmount as unmountNewsChart,
} from "./chart/newsChart";

window.NewsChart = { mount: mountNewsChart, unmount: unmountNewsChart };
