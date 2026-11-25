import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogPager-BwJ4cMnn.js";
import { t as n } from "./CatalogState-Bkly-MlG.js";
import { C as r, D as i, O as a, S as o, d as ee, f as s, k as c } from "./catalog-DCoO3A8a.js";
import { t as te } from "./AssetThumbnail-DCu2iAUU.js";
import { i as l, n as u, o as ne } from "./assets-DNUkY-zI.js";
import { AtriumIntegrationScreen as re, AtriumPageHeader as ie, AtriumSelect as ae, AtriumState as oe } from "@refactor-circus/atrium";
import { UButton as se, UCard as ce, UFormField as le, UInput as ue, ULink as de, UTable as fe } from "@refactor-circus/atrium/ui";
import { Fragment as pe, computed as d, createBlock as f, createCommentVNode as me, createElementBlock as p, createElementVNode as m, createTextVNode as h, createVNode as g, defineComponent as _, openBlock as v, reactive as y, renderList as he, shallowRef as b, toDisplayString as x, unref as S, useTemplateRef as C, watch as ge, withCtx as w, withModifiers as T } from "vue";
import { useI18n as E } from "vue-i18n";
import { useRoute as D, useRouter as O } from "vue-router";
// src/pages/assets/index.vue?vue&type=script&setup=true&lang.ts
var k = {
	class: "space-y-4",
	"data-testid": "showroom-assets-page"
}, A = { class: "flex gap-1" }, j = { class: "space-y-4" }, M = { class: "font-semibold" }, N = { class: "space-y-4" }, P = {
	key: 0,
	class: "grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5",
	"data-testid": "showroom-assets-grid"
}, F = ["data-testid"], I = ["data-testid"], L = { class: "font-mono text-xs" }, _e = { class: "tabular-nums" }, ve = { class: "text-muted" }, R = /* @__PURE__ */ _({
	__name: "index",
	setup(_) {
		let { t: R, locale: z } = E(), B = D(), V = O(), H = a(), U = d(() => {
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
		}), ye = d(() => JSON.stringify(U.value)), W = d(() => o(B.query.view) === "list" ? "list" : "grid"), { client: G, data: K, pending: be, failed: xe, status: Se, reload: Ce } = c(async (e) => r(await e.GET("/assets", { params: { query: i(U.value) } })), ye), q = d(() => K.value?.data ?? []), J = y({
			search: "",
			type: "",
			product: ""
		});
		ge(() => B.query, () => {
			J.search = o(B.query.search) ?? "", J.type = o(B.query.type) ?? "", J.product = o(B.query.product) ?? "";
		}, { immediate: !0 });
		function we() {
			V.replace({ query: {
				...W.value === "list" ? { view: "list" } : {},
				...J.search.trim() ? { search: J.search.trim() } : {},
				...J.type ? { type: J.type } : {},
				...J.product.trim() ? { product: J.product.trim() } : {}
			} });
		}
		function Y() {
			V.replace({ query: W.value === "list" ? { view: "list" } : {} });
		}
		function X(e) {
			V.replace({ query: {
				...B.query,
				view: e === "list" ? "list" : void 0
			} });
		}
		let Z = y({
			code: "",
			label: ""
		}), Q = b(null), $ = C("fileInput"), Te = d(() => u.map((e) => ({
			label: R(`showroom.assets.types.${e.key}`),
			value: e.value
		}))), Ee = d(() => [
			{
				accessorKey: "code",
				header: R("showroom.catalog.fields.code")
			},
			{
				id: "label",
				header: R("showroom.catalog.fields.label")
			},
			{
				accessorKey: "mime_type",
				header: R("showroom.catalog.fields.type")
			},
			{
				accessorKey: "size",
				header: R("showroom.assets.size"),
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			},
			{
				accessorKey: "updated_at",
				header: R("showroom.catalog.fields.updated")
			}
		]);
		function De(e) {
			Q.value = e.target.files?.[0] ?? null;
		}
		async function Oe() {
			let e = Q.value;
			if (!G || !e) return;
			let t = new FormData();
			t.append("file", e), Z.code.trim() && t.append("code", Z.code.trim()), Z.label.trim() && t.append(`labels[${z.value}]`, Z.label.trim()), await H.run(async () => r(await G.POST("/assets", {
				body: t,
				bodySerializer: (e) => e
			}))) && (Z.code = "", Z.label = "", Q.value = null, $.value?.inputRef && ($.value.inputRef.value = ""), await Ce());
		}
		function ke(e) {
			return s(e.labels, z.value, e.code);
		}
		return (r, i) => {
			let a = se, o = ie, s = ue, c = le, u = ce, d = ae, _ = oe, y = de, b = fe, C = re;
			return v(), p("div", k, [g(o, { title: S(R)("showroom.assets.title") }, {
				default: w(() => [m("div", A, [g(a, {
					variant: S(W) === "grid" ? "solid" : "ghost",
					color: "neutral",
					size: "sm",
					icon: "i-lucide-layout-grid",
					"aria-label": S(R)("showroom.assets.grid"),
					"data-testid": "showroom-assets-view-grid",
					onClick: i[0] ||= (e) => X("grid")
				}, null, 8, ["variant", "aria-label"]), g(a, {
					variant: S(W) === "list" ? "solid" : "ghost",
					color: "neutral",
					size: "sm",
					icon: "i-lucide-list",
					"aria-label": S(R)("showroom.assets.list"),
					"data-testid": "showroom-assets-view-list",
					onClick: i[1] ||= (e) => X("list")
				}, null, 8, ["variant", "aria-label"])])]),
				_: 1
			}, 8, ["title"]), g(C, { "screen-key": "showroom.assets" }, {
				default: w(() => [m("div", j, [
					g(u, { "data-testid": "showroom-asset-upload-card" }, {
						header: w(() => [m("h2", M, x(S(R)("showroom.assets.upload")), 1)]),
						default: w(() => [m("form", {
							class: "flex flex-wrap items-start gap-3",
							"data-testid": "showroom-asset-upload-form",
							onSubmit: T(Oe, ["prevent"])
						}, [
							g(c, {
								label: S(R)("showroom.assets.file"),
								error: S(H).error("file"),
								required: ""
							}, {
								default: w(() => [g(s, {
									ref_key: "fileInput",
									ref: $,
									type: "file",
									required: "",
									"data-testid": "showroom-asset-upload-file",
									onChange: De
								}, null, 512)]),
								_: 1
							}, 8, ["label", "error"]),
							g(c, {
								label: S(R)("showroom.catalog.fields.code"),
								hint: S(R)("showroom.assets.code_hint"),
								error: S(H).error("code")
							}, {
								default: w(() => [g(s, {
									modelValue: S(Z).code,
									"onUpdate:modelValue": i[2] ||= (e) => S(Z).code = e,
									class: "w-48",
									"data-testid": "showroom-asset-upload-code"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, [
								"label",
								"hint",
								"error"
							]),
							g(c, {
								label: S(R)("showroom.catalog.fields.label_in", { locale: S(z) }),
								error: S(H).error("labels")
							}, {
								default: w(() => [g(s, {
									modelValue: S(Z).label,
									"onUpdate:modelValue": i[3] ||= (e) => S(Z).label = e,
									class: "w-56",
									"data-testid": "showroom-asset-upload-label"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							g(a, {
								type: "submit",
								icon: "i-lucide-upload",
								loading: S(H).busy.value,
								class: "mt-field-label",
								"data-testid": "showroom-asset-upload-submit"
							}, {
								default: w(() => [h(x(S(R)("showroom.assets.upload_button")), 1)]),
								_: 1
							}, 8, ["loading"])
						], 32), g(e, {
							action: S(H),
							class: "mt-3"
						}, null, 8, ["action"])]),
						_: 1
					}),
					g(u, null, {
						default: w(() => [m("form", {
							class: "flex flex-wrap items-start gap-3",
							"data-testid": "showroom-assets-filters",
							onSubmit: T(we, ["prevent"])
						}, [
							g(c, { label: S(R)("showroom.catalog.filters.search") }, {
								default: w(() => [g(s, {
									modelValue: S(J).search,
									"onUpdate:modelValue": i[4] ||= (e) => S(J).search = e,
									class: "w-56",
									"data-testid": "showroom-assets-search"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label"]),
							g(c, { label: S(R)("showroom.catalog.fields.type") }, {
								default: w(() => [g(d, {
									modelValue: S(J).type,
									"onUpdate:modelValue": i[5] ||= (e) => S(J).type = e,
									items: S(Te),
									blank: S(R)("showroom.assets.all_types"),
									"blank-value": null,
									class: "w-40",
									"data-testid": "showroom-assets-type"
								}, null, 8, [
									"modelValue",
									"items",
									"blank"
								])]),
								_: 1
							}, 8, ["label"]),
							g(c, { label: S(R)("showroom.assets.product") }, {
								default: w(() => [g(s, {
									modelValue: S(J).product,
									"onUpdate:modelValue": i[6] ||= (e) => S(J).product = e,
									class: "w-44",
									"data-testid": "showroom-assets-product"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label"]),
							g(a, {
								type: "submit",
								icon: "i-lucide-filter",
								class: "mt-field-label"
							}, {
								default: w(() => [h(x(S(R)("showroom.catalog.filters.filter")), 1)]),
								_: 1
							}),
							g(a, {
								variant: "ghost",
								color: "neutral",
								icon: "i-lucide-x",
								class: "mt-field-label",
								onClick: Y
							}, {
								default: w(() => [h(x(S(R)("showroom.catalog.filters.clear")), 1)]),
								_: 1
							})
						], 32)]),
						_: 1
					}),
					g(n, {
						ready: !S(be) && S(K) !== null,
						failed: S(xe),
						status: S(Se)
					}, {
						default: w(() => [m("div", N, [S(W) === "grid" ? (v(), p("div", P, [S(q).length === 0 ? (v(), f(_, {
							key: 0,
							compact: "",
							class: "col-span-full",
							title: S(R)("showroom.assets.empty"),
							"data-testid": "showroom-assets-empty"
						}, null, 8, ["title"])) : me("", !0), (v(!0), p(pe, null, he(S(q), (e) => (v(), p("div", {
							key: e.code,
							class: "rounded-md border border-default p-2 hover:border-primary",
							"data-testid": `showroom-asset-tile-${e.code}`
						}, [g(y, {
							to: S(l)(e.code),
							class: "flex flex-col gap-2"
						}, {
							default: w(() => [g(te, {
								asset: e,
								caption: e.code
							}, null, 8, ["asset", "caption"])]),
							_: 2
						}, 1032, ["to"])], 8, F))), 128))])) : (v(), f(b, {
							key: 1,
							data: S(q),
							columns: S(Ee),
							"data-testid": "showroom-assets-table"
						}, {
							empty: w(() => [g(_, {
								compact: "",
								title: S(R)("showroom.assets.empty"),
								"data-testid": "showroom-assets-empty"
							}, null, 8, ["title"])]),
							"code-cell": w(({ row: { original: e } }) => [m("span", { "data-testid": `showroom-asset-row-${e.code}` }, [g(y, {
								to: S(l)(e.code),
								class: "font-mono font-medium hover:underline"
							}, {
								default: w(() => [h(x(e.code), 1)]),
								_: 2
							}, 1032, ["to"])], 8, I)]),
							"label-cell": w(({ row: { original: e } }) => [h(x(ke(e)), 1)]),
							"mime_type-cell": w(({ row: { original: e } }) => [m("span", L, x(e.mime_type ?? S(R)("showroom.catalog.none")), 1)]),
							"size-cell": w(({ row: { original: e } }) => [m("span", _e, x(S(ne)(e.size)), 1)]),
							"updated_at-cell": w(({ row: { original: e } }) => [m("span", ve, x(S(ee)(e.updated_at) ?? S(R)("showroom.catalog.none")), 1)]),
							_: 1
						}, 8, ["data", "columns"])), g(t, {
							next: S(K)?.meta?.next_cursor ?? null,
							prev: S(K)?.meta?.prev_cursor ?? null,
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
