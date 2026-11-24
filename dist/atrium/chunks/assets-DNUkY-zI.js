import { b as e, v as t } from "./catalog-DCoO3A8a.js";
// src/components/assets/assets.ts
var n = [
	"product",
	"product_model",
	"owner"
], r = [
	{
		value: "image/*",
		key: "images"
	},
	{
		value: "application/pdf",
		key: "pdf"
	},
	{
		value: "video/*",
		key: "videos"
	}
];
function i(e) {
	return (e.mime_type ?? "").startsWith("image/");
}
function a(e) {
	let t = e.filename.lastIndexOf(".");
	return (t > 0 ? e.filename.slice(t + 1) : "") || e.mime_type || "";
}
function o(e) {
	return `${(e / 1024).toFixed(1)} KB`;
}
function s(e) {
	return `/showroom/assets/${encodeURIComponent(e)}`;
}
function c(n) {
	switch (n.type) {
		case "product_model": return t(n.target);
		case "owner": return `/showroom/owners?search=${encodeURIComponent(n.target)}`;
		default: return e(n.target);
	}
}
function l(e, t) {
	for (let [n, r] of Object.entries(t)) r !== null && r !== "" && e.append(`labels[${n}]`, r);
}
export { a, c, s as i, r as n, o, l as r, i as s, n as t };
