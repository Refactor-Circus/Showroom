import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogPager-BwJ4cMnn.js";
import { t as n } from "./CatalogState-Bkly-MlG.js";
import { C as r, D as i, O as a, S as o, d as ee, f as s, k as c } from "./catalog-DCoO3A8a.js";
import { t as te } from "./AssetThumbnail-DCu2iAUU.js";
import { i as l, n as ne, o as re } from "./assets-DNUkY-zI.js";
import { AtriumIntegrationScreen as ie } from "@refactor-circus/atrium";
import { UButton as ae, UCard as oe, UFormField as se, UInput as ce } from "@refactor-circus/atrium/ui";
import { Fragment as u, computed as d, createElementBlock as f, createElementVNode as p, createTextVNode as m, createVNode as h, defineComponent as g, openBlock as _, reactive as v, renderList as y, resolveComponent as le, shallowRef as ue, toDisplayString as b, unref as x, useTemplateRef as de, vModelSelect as fe, watch as pe, withCtx as S, withDirectives as me, withModifiers as C } from "vue";
import { useI18n as he } from "vue-i18n";
import { useRoute as ge, useRouter as w } from "vue-router";
// src/pages/assets/index.vue?vue&type=script&setup=true&lang.ts
var T = {
	class: "space-y-4",
	"data-testid": "showroom-assets-page"
}, E = { class: "flex flex-wrap items-start justify-between gap-3" }, D = { class: "text-xl font-semibold" }, O = { class: "flex gap-1" }, k = { class: "space-y-4" }, A = { class: "font-semibold" }, j = { value: "" }, M = ["value"], N = { class: "space-y-4" }, P = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "showroom-assets-empty"
}, F = {
	key: 1,
	class: "grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5",
	"data-testid": "showroom-assets-grid"
}, I = ["data-testid"], L = {
	key: 2,
	class: "w-full text-left text-sm",
	"data-testid": "showroom-assets-table"
}, _e = { class: "text-muted" }, ve = { class: "py-2 pr-3 font-medium" }, ye = { class: "py-2 pr-3 font-medium" }, be = { class: "py-2 pr-3 font-medium" }, xe = { class: "py-2 pr-3 font-medium" }, Se = { class: "py-2 font-medium" }, Ce = { class: "divide-y divide-default" }, we = ["data-testid"], Te = { class: "py-2 pr-3" }, Ee = { class: "py-2 pr-3" }, De = { class: "py-2 pr-3 font-mono text-xs" }, Oe = { class: "py-2 pr-3 tabular-nums" }, ke = { class: "py-2 text-muted" }, R = /* @__PURE__ */ g({
	__name: "index",
	setup(g) {
		let { t: R, locale: z } = he(), B = ge(), V = w(), H = a(), U = d(() => {
			let e = {};
			for (let t of [
				"search",
				"type",
				"product",
				"cursor"
			]) {
				let n = o(B.query[t]);
				n && (e[t] = n);
			}
			return e;
		}), Ae = d(() => JSON.stringify(U.value)), W = d(() => o(B.query.view) === "list" ? "list" : "grid"), { client: G, data: K, pending: je, failed: Me, status: Ne, reload: Pe } = c(async (e) => r(await e.GET("/assets", { params: { query: i(U.value) } })), Ae), q = d(() => K.value?.data ?? []), J = v({
			search: "",
			type: "",
			product: ""
		});
		pe(() => B.query, () => {
			J.search = o(B.query.search) ?? "", J.type = o(B.query.type) ?? "", J.product = o(B.query.product) ?? "";
		}, { immediate: !0 });
		function Y() {
			V.replace({ query: {
				...W.value === "list" ? { view: "list" } : {},
				...J.search.trim() ? { search: J.search.trim() } : {},
				...J.type ? { type: J.type } : {},
				...J.product.trim() ? { product: J.product.trim() } : {}
			} });
		}
		function Fe() {
			V.replace({ query: W.value === "list" ? { view: "list" } : {} });
		}
		function X(e) {
			V.replace({ query: {
				...B.query,
				view: e === "list" ? "list" : void 0
			} });
		}
		let Z = v({
			code: "",
			label: ""
		}), Q = ue(null), $ = de("fileInput");
		function Ie(e) {
			Q.value = e.target.files?.[0] ?? null;
		}
		async function Le() {
			let e = Q.value;
			if (!G || !e) return;
			let t = new FormData();
			t.append("file", e), Z.code.trim() && t.append("code", Z.code.trim()), Z.label.trim() && t.append(`labels[${z.value}]`, Z.label.trim()), await H.run(async () => r(await G.POST("/assets", {
				body: t,
				bodySerializer: (e) => e
			}))) && (Z.code = "", Z.label = "", Q.value = null, $.value && ($.value.value = ""), await Pe());
		}
		function Re(e) {
			return s(e.labels, z.value, e.code);
		}
		return (r, i) => {
			let a = ae, o = se, s = ce, c = oe, d = le("RouterLink"), g = ie;
			return _(), f("div", T, [p("div", E, [p("h1", D, b(x(R)("showroom.assets.title")), 1), p("div", O, [h(a, {
				variant: x(W) === "grid" ? "solid" : "ghost",
				color: "neutral",
				size: "sm",
				icon: "i-lucide-layout-grid",
				"aria-label": x(R)("showroom.assets.grid"),
				"data-testid": "showroom-assets-view-grid",
				onClick: i[0] ||= (e) => X("grid")
			}, null, 8, ["variant", "aria-label"]), h(a, {
				variant: x(W) === "list" ? "solid" : "ghost",
				color: "neutral",
				size: "sm",
				icon: "i-lucide-list",
				"aria-label": x(R)("showroom.assets.list"),
				"data-testid": "showroom-assets-view-list",
				onClick: i[1] ||= (e) => X("list")
			}, null, 8, ["variant", "aria-label"])])]), h(g, { "screen-key": "showroom.assets" }, {
				default: S(() => [p("div", k, [
					h(c, { "data-testid": "showroom-asset-upload-card" }, {
						header: S(() => [p("h2", A, b(x(R)("showroom.assets.upload")), 1)]),
						default: S(() => [p("form", {
							class: "flex flex-wrap items-end gap-3",
							"data-testid": "showroom-asset-upload-form",
							onSubmit: C(Le, ["prevent"])
						}, [
							h(o, {
								label: x(R)("showroom.assets.file"),
								error: x(H).error("file"),
								required: ""
							}, {
								default: S(() => [p("input", {
									ref_key: "fileInput",
									ref: $,
									type: "file",
									required: "",
									class: "text-sm",
									"data-testid": "showroom-asset-upload-file",
									onChange: Ie
								}, null, 544)]),
								_: 1
							}, 8, ["label", "error"]),
							h(o, {
								label: x(R)("showroom.catalog.fields.code"),
								hint: x(R)("showroom.assets.code_hint"),
								error: x(H).error("code")
							}, {
								default: S(() => [h(s, {
									modelValue: x(Z).code,
									"onUpdate:modelValue": i[2] ||= (e) => x(Z).code = e,
									class: "w-48",
									"data-testid": "showroom-asset-upload-code"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, [
								"label",
								"hint",
								"error"
							]),
							h(o, {
								label: x(R)("showroom.catalog.fields.label_in", { locale: x(z) }),
								error: x(H).error("labels")
							}, {
								default: S(() => [h(s, {
									modelValue: x(Z).label,
									"onUpdate:modelValue": i[3] ||= (e) => x(Z).label = e,
									class: "w-56",
									"data-testid": "showroom-asset-upload-label"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							h(a, {
								type: "submit",
								icon: "i-lucide-upload",
								loading: x(H).busy.value,
								"data-testid": "showroom-asset-upload-submit"
							}, {
								default: S(() => [m(b(x(R)("showroom.assets.upload_button")), 1)]),
								_: 1
							}, 8, ["loading"])
						], 32), h(e, {
							action: x(H),
							class: "mt-3"
						}, null, 8, ["action"])]),
						_: 1
					}),
					h(c, null, {
						default: S(() => [p("form", {
							class: "atrium-querybar",
							"data-testid": "showroom-assets-filters",
							onSubmit: C(Y, ["prevent"])
						}, [
							h(o, { label: x(R)("showroom.catalog.filters.search") }, {
								default: S(() => [h(s, {
									modelValue: x(J).search,
									"onUpdate:modelValue": i[4] ||= (e) => x(J).search = e,
									class: "w-56",
									"data-testid": "showroom-assets-search"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label"]),
							h(o, { label: x(R)("showroom.catalog.fields.type") }, {
								default: S(() => [me(p("select", {
									"onUpdate:modelValue": i[5] ||= (e) => x(J).type = e,
									class: "w-40 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
									"data-testid": "showroom-assets-type"
								}, [p("option", j, b(x(R)("showroom.assets.all_types")), 1), (_(!0), f(u, null, y(x(ne), (e) => (_(), f("option", {
									key: e.value,
									value: e.value
								}, b(x(R)(`showroom.assets.types.${e.key}`)), 9, M))), 128))], 512), [[fe, x(J).type]])]),
								_: 1
							}, 8, ["label"]),
							h(o, { label: x(R)("showroom.assets.product") }, {
								default: S(() => [h(s, {
									modelValue: x(J).product,
									"onUpdate:modelValue": i[6] ||= (e) => x(J).product = e,
									class: "w-44",
									"data-testid": "showroom-assets-product"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label"]),
							h(a, {
								type: "submit",
								icon: "i-lucide-filter"
							}, {
								default: S(() => [m(b(x(R)("showroom.catalog.filters.filter")), 1)]),
								_: 1
							}),
							h(a, {
								variant: "ghost",
								color: "neutral",
								icon: "i-lucide-x",
								onClick: Fe
							}, {
								default: S(() => [m(b(x(R)("showroom.catalog.filters.clear")), 1)]),
								_: 1
							})
						], 32)]),
						_: 1
					}),
					h(n, {
						ready: !x(je) && x(K) !== null,
						failed: x(Me),
						status: x(Ne)
					}, {
						default: S(() => [p("div", N, [x(q).length === 0 ? (_(), f("p", P, b(x(R)("showroom.assets.empty")), 1)) : x(W) === "grid" ? (_(), f("div", F, [(_(!0), f(u, null, y(x(q), (e) => (_(), f("div", {
							key: e.code,
							class: "rounded-md border border-default p-2 hover:border-primary",
							"data-testid": `showroom-asset-tile-${e.code}`
						}, [h(d, {
							to: x(l)(e.code),
							class: "flex flex-col gap-2"
						}, {
							default: S(() => [h(te, {
								asset: e,
								caption: e.code
							}, null, 8, ["asset", "caption"])]),
							_: 2
						}, 1032, ["to"])], 8, I))), 128))])) : (_(), f("table", L, [p("thead", _e, [p("tr", null, [
							p("th", ve, b(x(R)("showroom.catalog.fields.code")), 1),
							p("th", ye, b(x(R)("showroom.catalog.fields.label")), 1),
							p("th", be, b(x(R)("showroom.catalog.fields.type")), 1),
							p("th", xe, b(x(R)("showroom.assets.size")), 1),
							p("th", Se, b(x(R)("showroom.catalog.fields.updated")), 1)
						])]), p("tbody", Ce, [(_(!0), f(u, null, y(x(q), (e) => (_(), f("tr", {
							key: e.code,
							"data-testid": `showroom-asset-row-${e.code}`
						}, [
							p("td", Te, [h(d, {
								to: x(l)(e.code),
								class: "font-mono font-medium hover:underline"
							}, {
								default: S(() => [m(b(e.code), 1)]),
								_: 2
							}, 1032, ["to"])]),
							p("td", Ee, b(Re(e)), 1),
							p("td", De, b(e.mime_type ?? x(R)("showroom.catalog.none")), 1),
							p("td", Oe, b(x(re)(e.size)), 1),
							p("td", ke, b(x(ee)(e.updated_at) ?? x(R)("showroom.catalog.none")), 1)
						], 8, we))), 128))])])), h(t, {
							next: x(K)?.meta?.next_cursor ?? null,
							prev: x(K)?.meta?.prev_cursor ?? null,
							testid: "showroom-assets"
						}, null, 8, ["next", "prev"])])]),
						_: 1
					}, 8, [
						"ready",
						"failed",
						"status"
					])
				])]),
				_: 1
			})]);
		};
	}
});
export { R as default };
