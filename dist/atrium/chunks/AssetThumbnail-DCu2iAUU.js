import { a as e, s as t } from "./assets-DNUkY-zI.js";
import { Fragment as n, createElementBlock as r, createElementVNode as i, defineComponent as a, openBlock as o, toDisplayString as s, unref as c } from "vue";
// src/components/assets/AssetThumbnail.vue?vue&type=script&setup=true&lang.ts
var l = { class: "flex aspect-square items-center justify-center overflow-hidden rounded-md bg-elevated" }, u = ["src", "alt"], d = {
	key: 1,
	class: "font-mono text-xs uppercase text-muted"
}, f = { class: "truncate text-xs" }, p = /* @__PURE__ */ a({
	__name: "AssetThumbnail",
	props: {
		asset: {},
		caption: {}
	},
	setup(a) {
		return (p, m) => (o(), r(n, null, [i("div", l, [c(t)(a.asset) ? (o(), r("img", {
			key: 0,
			src: a.asset.url,
			alt: a.caption,
			loading: "lazy",
			class: "size-full object-contain"
		}, null, 8, u)) : (o(), r("span", d, s(c(e)(a.asset)), 1))]), i("span", f, s(a.caption), 1)], 64));
	}
});
export { p as t };
