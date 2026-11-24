import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogState-Bkly-MlG.js";
import { t as n } from "./ExternalIds-ATlFXCLK.js";
import { A as r, C as i, D as a, O as o, a as s, c, f as l, j as u, k as d, s as f } from "./catalog-DCoO3A8a.js";
import { u as p } from "./structure-DbqcM-iO.js";
import { t as m } from "./AssetThumbnail-DCu2iAUU.js";
import { i as h } from "./assets-DNUkY-zI.js";
import { n as g } from "./owners-C7wgNkGA.js";
import { AtriumIntegrationScreen as _, AtriumState as v, navigateTo as y } from "@refactor-circus/atrium";
import { UBadge as ee, UButton as b, UCard as te, UCheckbox as ne, UFormField as re, UIcon as ie, UInput as x, ULink as S, UPageHeader as ae, USelect as oe } from "@refactor-circus/atrium/ui";
import { Fragment as C, computed as w, createBlock as T, createCommentVNode as E, createElementBlock as D, createElementVNode as O, createTextVNode as k, createVNode as A, defineComponent as j, isRef as se, openBlock as M, reactive as N, ref as P, renderList as F, shallowRef as I, toDisplayString as L, unref as R, useTemplateRef as z, watch as ce, withCtx as B, withModifiers as V } from "vue";
import { useI18n as H } from "vue-i18n";
import { useRoute as le } from "vue-router";
// src/components/owners/OwnerAssets.vue?vue&type=script&setup=true&lang.ts
var U = { class: "font-semibold" }, W = { class: "space-y-4" }, G = {
	key: 1,
	class: "grid grid-cols-2 gap-3 sm:grid-cols-4"
}, ue = /* @__PURE__ */ j({
	__name: "OwnerAssets",
	props: {
		assets: {},
		owner: {}
	},
	emits: ["changed"],
	setup(t, { emit: n }) {
		let r = t, a = n, { t: s } = H(), c = u(), l = o(), d = I(null), f = P("logo"), p = z("fileInput");
		function g(e) {
			d.value = e.target.files?.[0] ?? null;
		}
		function _(e) {
			return [e.role, e.inherited ? s("showroom.catalog.inherited") : e.code].filter((e) => e).join(" · ");
		}
		async function y() {
			let e = d.value;
			c && e && await l.run(async () => {
				let t = new FormData();
				t.append("file", e);
				let n = i(await c.POST("/assets", {
					body: t,
					bodySerializer: (e) => e
				})).data;
				i(await c.POST("/assets/{asset}/links", {
					params: { path: { asset: n.code } },
					body: {
						type: "owner",
						target: r.owner,
						...f.value.trim() ? { role: f.value.trim() } : {}
					}
				}));
			}) && (d.value = null, p.value?.inputRef && (p.value.inputRef.value = ""), a("changed"));
		}
		return (t, n) => {
			let i = v, a = S, o = x, c = re, u = b, d = te;
			return M(), T(d, { "data-testid": "showroom-owner-assets" }, {
				header: B(() => [O("h2", U, L(R(s)("showroom.catalog.assets.title")), 1)]),
				default: B(() => [O("div", W, [
					r.assets.length === 0 ? (M(), T(i, {
						key: 0,
						compact: "",
						title: R(s)("showroom.catalog.assets.none"),
						"data-testid": "showroom-owner-assets-empty"
					}, null, 8, ["title"])) : (M(), D("div", G, [(M(!0), D(C, null, F(r.assets, (e) => (M(), T(a, {
						key: `${e.inherited ? "inherited" : "own"}-${e.code}`,
						to: R(h)(e.code),
						class: "flex flex-col gap-2 rounded-md border border-default p-2 hover:border-primary",
						"data-testid": `showroom-asset-${e.code}`
					}, {
						default: B(() => [A(m, {
							asset: {
								filename: e.code,
								mime_type: e.mime_type,
								url: e.url ?? ""
							},
							caption: _(e)
						}, null, 8, ["asset", "caption"])]),
						_: 2
					}, 1032, ["to", "data-testid"]))), 128))])),
					A(e, { action: R(l) }, null, 8, ["action"]),
					O("form", {
						class: "flex flex-wrap items-end gap-3",
						"data-testid": "showroom-asset-upload",
						onSubmit: V(y, ["prevent"])
					}, [
						A(c, {
							label: R(s)("showroom.catalog.assets.upload_and_link"),
							error: R(l).error("file")
						}, {
							default: B(() => [A(o, {
								ref: "fileInput",
								type: "file",
								required: "",
								"data-testid": "showroom-asset-file",
								onChange: g
							}, null, 512)]),
							_: 1
						}, 8, ["label", "error"]),
						A(c, {
							label: R(s)("showroom.catalog.assets.role"),
							error: R(l).error("role")
						}, {
							default: B(() => [A(o, {
								modelValue: R(f),
								"onUpdate:modelValue": n[0] ||= (e) => se(f) ? f.value = e : null,
								class: "w-32",
								"data-testid": "showroom-asset-role"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						A(u, {
							type: "submit",
							icon: "i-lucide-upload",
							loading: R(l).busy.value,
							"data-testid": "showroom-asset-upload-submit"
						}, {
							default: B(() => [k(L(R(s)("showroom.catalog.assets.upload")), 1)]),
							_: 1
						}, 8, ["loading"])
					], 32)
				])]),
				_: 1
			});
		};
	}
}), de = {
	class: "space-y-4",
	"data-testid": "showroom-owner"
}, fe = {
	key: 0,
	class: "space-y-5"
}, pe = {
	key: 0,
	class: "flex flex-wrap items-center gap-1 text-sm text-muted",
	"data-testid": "showroom-owner-chain"
}, me = { class: "font-mono text-default" }, he = { "data-testid": "showroom-owner-title" }, ge = {
	class: "font-mono text-sm",
	"data-testid": "showroom-owner-subtitle"
}, _e = {
	class: "text-sm text-muted",
	"data-testid": "showroom-owner-counts"
}, ve = { class: "font-semibold" }, ye = { class: "flex flex-wrap items-end gap-4" }, be = { class: "font-semibold" }, xe = { class: "flex flex-col gap-4" }, Se = { class: "text-sm" }, Ce = { class: "text-muted" }, we = {
	class: "font-mono",
	"data-testid": "showroom-owner-slug"
}, Te = {
	key: 0,
	"data-testid": "showroom-own-flags"
}, Ee = { class: "mb-1 text-xs font-medium uppercase text-muted" }, De = { class: "flex flex-wrap gap-3" }, Oe = { class: "font-mono text-xs" }, ke = {
	key: 0,
	class: "ms-1.5 text-muted"
}, Ae = {
	key: 0,
	class: "text-sm text-muted"
}, je = {
	key: 0,
	class: "text-xs text-error"
}, Me = { "data-testid": "showroom-effective-flags" }, Ne = { class: "mb-1 text-xs font-medium uppercase text-muted" }, Pe = { class: "flex flex-wrap gap-3" }, Fe = ["data-effective-flag"], Ie = {
	key: 0,
	class: "text-xs text-muted"
}, Le = {
	key: 0,
	class: "text-sm text-muted"
}, Re = { class: "grid gap-5 lg:grid-cols-2" }, ze = { class: "font-semibold" }, Be = { class: "space-y-4" }, Ve = {
	key: 0,
	class: "text-sm text-muted"
}, He = {
	key: 1,
	class: "flex flex-wrap gap-2"
}, Ue = {
	key: 2,
	class: "text-xs text-error"
}, K = /* @__PURE__ */ j({
	__name: "[owner]",
	setup(u) {
		let { t: m, locale: h } = H(), v = le(), j = o(), I = o(), z = w(() => String(v.params.owner)), { client: U, data: W, pending: G, failed: K, status: We, reload: q } = d(async (e) => i(await e.GET("/owners/{owner}", { params: { path: { owner: z.value } } })).data, z), J = w(() => W.value), Ge = w(() => (J.value?.chain ?? []).slice(0, -1)), Y = r(async (e) => i(await e.GET("/owner-types", { params: { query: { per_page: 100 } } }))), Ke = r(async (e) => i(await e.GET("/flags", { params: { query: { per_page: 100 } } }))), X = w(() => Ke.value.filter((e) => e.applies_to.includes("owner"))), qe = w(() => Je.value.map((e) => ({
			label: Xe(e.code),
			value: e.code
		}))), Je = w(() => Y.value.filter((e) => e.parent_types === null || e.parent_types === void 0 || e.parent_types.includes(J.value?.type ?? ""))), Z = N({
			label: "",
			parent: "",
			enabled: !0,
			flags: []
		}), Q = P(c(null)), $ = N({
			code: "",
			type: ""
		});
		ce(W, () => {
			let e = J.value;
			e && (Z.label = e.labels[h.value] ?? "", Z.parent = e.parent ?? "", Z.enabled = e.enabled, Z.flags = [...e.flags ?? []], Q.value = c(e.external_ids));
		}, { immediate: !0 });
		function Ye(e, t) {
			Z.flags = t ? [...Z.flags.filter((t) => t !== e), e] : Z.flags.filter((t) => t !== e);
		}
		function Xe(e) {
			let t = Y.value.find((t) => t.code === e);
			return t ? l(t.labels, h.value, t.code) : e ?? "";
		}
		async function Ze() {
			let e = J.value;
			if (!U || !e) return;
			let t = {
				labels: p(e.labels, h.value, Z.label),
				parent: s(Z.parent),
				enabled: Z.enabled,
				external_ids: f(Q.value),
				...e.flags === void 0 ? {} : { flags: Z.flags }
			};
			await j.run(async () => i(await U.PATCH("/owners/{owner}", {
				params: { path: { owner: z.value } },
				body: a(t)
			}))) && await q();
		}
		async function Qe() {
			let e = J.value;
			if (!U || !e) return;
			let t = {
				code: $.code.trim(),
				type: $.type,
				parent: e.code
			};
			await I.run(async () => i(await U.POST("/owners", { body: a(t) }))) && ($.code = "", await q());
		}
		async function $e() {
			U && window.confirm(m("showroom.owners.confirm_delete")) && await j.run(async () => i(await U.DELETE("/owners/{owner}", { params: { path: { owner: z.value } } }))) && await y("/showroom/owners");
		}
		return (r, i) => {
			let a = S, o = ie, s = ee, c = b, u = ae, d = x, f = re, p = ne, v = te, y = oe, w = _;
			return M(), D("div", de, [A(w, {
				"screen-key": "showroom.owner",
				record: R(z)
			}, {
				default: B(() => [A(t, {
					ready: !R(G) && R(J) !== null,
					failed: R(K),
					status: R(We)
				}, {
					default: B(() => [R(J) ? (M(), D("div", fe, [
						(R(J).chain ?? []).length > 1 ? (M(), D("nav", pe, [(M(!0), D(C, null, F(R(Ge), (e) => (M(), D(C, { key: e }, [A(a, {
							to: R(g)(e),
							class: "font-mono hover:underline"
						}, {
							default: B(() => [k(L(e), 1)]),
							_: 2
						}, 1032, ["to"]), A(o, {
							name: "i-lucide-chevron-right",
							class: "size-3"
						})], 64))), 128)), O("span", me, L(R(J).code), 1)])) : E("", !0),
						A(u, {
							headline: R(m)("showroom.owners.title"),
							ui: { title: "truncate" }
						}, {
							title: B(() => [O("span", he, L(R(l)(R(J).labels, R(h), R(J).code)), 1)]),
							description: B(() => [O("span", ge, L([R(J).code, R(J).type].filter((e) => e).join(" · ")), 1)]),
							links: B(() => [
								A(s, {
									color: R(J).enabled ? "success" : "neutral",
									variant: "subtle",
									"data-testid": "showroom-owner-enabled"
								}, {
									default: B(() => [k(L(R(J).enabled ? R(m)("showroom.catalog.enabled") : R(m)("showroom.catalog.disabled")), 1)]),
									_: 1
								}, 8, ["color"]),
								A(c, {
									to: `/showroom/products?owner=${encodeURIComponent(R(J).code)}`,
									variant: "outline",
									icon: "i-lucide-package",
									"data-testid": "showroom-owner-products"
								}, {
									default: B(() => [k(L(R(m)("showroom.owners.products")), 1)]),
									_: 1
								}, 8, ["to"]),
								A(c, {
									color: "error",
									variant: "outline",
									icon: "i-lucide-trash-2",
									loading: R(j).busy.value,
									"data-testid": "showroom-owner-delete",
									onClick: $e
								}, {
									default: B(() => [k(L(R(m)("showroom.catalog.delete")), 1)]),
									_: 1
								}, 8, ["loading"])
							]),
							_: 1
						}, 8, ["headline"]),
						O("p", _e, L(R(m)("showroom.owners.counts", {
							products: R(J).products_count ?? 0,
							models: R(J).product_models_count ?? 0
						})), 1),
						O("form", {
							class: "space-y-5",
							"data-testid": "showroom-owner-form",
							onSubmit: V(Ze, ["prevent"])
						}, [
							A(e, { action: R(j) }, null, 8, ["action"]),
							A(v, null, {
								header: B(() => [O("h2", ve, L(R(m)("showroom.owners.details")), 1)]),
								default: B(() => [O("div", ye, [
									A(f, {
										label: R(m)("showroom.catalog.fields.label_in", { locale: R(h) }),
										error: R(j).error("labels")
									}, {
										default: B(() => [A(d, {
											modelValue: R(Z).label,
											"onUpdate:modelValue": i[0] ||= (e) => R(Z).label = e,
											class: "w-56",
											"data-testid": "showroom-owner-label"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									A(f, {
										label: R(m)("showroom.catalog.fields.parent"),
										hint: R(m)("showroom.owners.move_hint"),
										error: R(j).error("parent")
									}, {
										default: B(() => [A(d, {
											modelValue: R(Z).parent,
											"onUpdate:modelValue": i[1] ||= (e) => R(Z).parent = e,
											class: "w-48",
											"data-testid": "showroom-owner-parent"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, [
										"label",
										"hint",
										"error"
									]),
									A(p, {
										modelValue: R(Z).enabled,
										"onUpdate:modelValue": i[2] ||= (e) => R(Z).enabled = e,
										label: R(m)("showroom.catalog.enabled"),
										class: "h-9 items-center",
										"data-testid": "showroom-owner-enabled-input"
									}, null, 8, ["modelValue", "label"])
								])]),
								_: 1
							}),
							A(v, { "data-testid": "showroom-owner-identity" }, {
								header: B(() => [O("h2", be, L(R(m)("showroom.catalog.products.identifiers_and_flags")), 1)]),
								default: B(() => [O("div", xe, [
									O("p", Se, [O("span", Ce, L(R(m)("showroom.catalog.fields.slug")) + ":", 1), O("span", we, L(R(J).slug), 1)]),
									A(n, {
										modelValue: R(Q),
										"onUpdate:modelValue": i[3] ||= (e) => se(Q) ? Q.value = e : null,
										"synced-at": R(J).external_ids_synced_at ?? {},
										error: R(j).error
									}, null, 8, [
										"modelValue",
										"synced-at",
										"error"
									]),
									R(J).flags === void 0 ? E("", !0) : (M(), D("div", Te, [
										O("p", Ee, L(R(m)("showroom.catalog.flags.own")), 1),
										O("div", De, [(M(!0), D(C, null, F(R(X), (e) => (M(), T(p, {
											key: e.code,
											"model-value": R(Z).flags.includes(e.code),
											"data-testid": `showroom-flag-${e.code}`,
											"onUpdate:modelValue": (t) => Ye(e.code, t === !0)
										}, {
											label: B(() => [O("span", Oe, L(e.code), 1), R(l)(e.labels, R(h), e.code) === e.code ? E("", !0) : (M(), D("span", ke, L(R(l)(e.labels, R(h), e.code)), 1))]),
											_: 2
										}, 1032, [
											"model-value",
											"data-testid",
											"onUpdate:modelValue"
										]))), 128)), R(X).length === 0 ? (M(), D("span", Ae, L(R(m)("showroom.owners.no_flags")), 1)) : E("", !0)]),
										R(j).error("flags") ? (M(), D("small", je, L(R(j).error("flags")), 1)) : E("", !0)
									])),
									O("div", Me, [O("p", Ne, L(R(m)("showroom.catalog.flags.effective")), 1), O("ul", Pe, [(M(!0), D(C, null, F(R(J).effective_flags ?? [], (e) => (M(), D("li", {
										key: e,
										class: "flex items-center gap-1.5 text-sm",
										"data-effective-flag": e
									}, [A(s, {
										color: (R(J).flags ?? []).includes(e) ? "primary" : "neutral",
										variant: "subtle"
									}, {
										default: B(() => [k(L(e), 1)]),
										_: 2
									}, 1032, ["color"]), (R(J).flags ?? []).includes(e) ? E("", !0) : (M(), D("span", Ie, L(R(m)("showroom.catalog.inherited")), 1))], 8, Fe))), 128)), (R(J).effective_flags ?? []).length === 0 ? (M(), D("li", Le, L(R(m)("showroom.catalog.none")), 1)) : E("", !0)])])
								])]),
								_: 1
							}),
							O("div", null, [A(c, {
								type: "submit",
								icon: "i-lucide-check",
								loading: R(j).busy.value,
								"data-testid": "showroom-owner-save"
							}, {
								default: B(() => [k(L(R(m)("showroom.catalog.save")), 1)]),
								_: 1
							}, 8, ["loading"])])
						], 32),
						O("div", Re, [A(v, { "data-testid": "showroom-owner-children" }, {
							header: B(() => [O("h2", ze, L(R(m)("showroom.owners.children")), 1)]),
							default: B(() => [O("div", Be, [
								(R(J).children ?? []).length === 0 ? (M(), D("p", Ve, L(R(m)("showroom.owners.no_children")), 1)) : (M(), D("ul", He, [(M(!0), D(C, null, F(R(J).children, (e) => (M(), D("li", { key: e }, [A(a, {
									to: R(g)(e),
									class: "font-mono text-sm hover:underline",
									"data-testid": `showroom-owner-child-${e}`
								}, {
									default: B(() => [k(L(e), 1)]),
									_: 2
								}, 1032, ["to", "data-testid"])]))), 128))])),
								O("form", {
									class: "flex flex-wrap items-end gap-3",
									"data-testid": "showroom-owner-child-form",
									onSubmit: V(Qe, ["prevent"])
								}, [
									A(e, {
										action: R(I),
										class: "w-full"
									}, null, 8, ["action"]),
									A(f, {
										label: R(m)("showroom.catalog.fields.code"),
										error: R(I).error("code"),
										required: ""
									}, {
										default: B(() => [A(d, {
											modelValue: R($).code,
											"onUpdate:modelValue": i[4] ||= (e) => R($).code = e,
											required: "",
											class: "w-40",
											"data-testid": "showroom-owner-child-code"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									A(f, {
										label: R(m)("showroom.catalog.fields.type"),
										error: R(I).error("type"),
										required: ""
									}, {
										default: B(() => [A(y, {
											modelValue: R($).type,
											"onUpdate:modelValue": i[5] ||= (e) => R($).type = e,
											items: R(qe),
											placeholder: R(m)("showroom.owners.choose_type"),
											required: "",
											class: "w-40",
											"data-testid": "showroom-owner-child-type"
										}, null, 8, [
											"modelValue",
											"items",
											"placeholder"
										])]),
										_: 1
									}, 8, ["label", "error"]),
									A(c, {
										type: "submit",
										icon: "i-lucide-plus",
										loading: R(I).busy.value,
										"data-testid": "showroom-owner-child-submit"
									}, {
										default: B(() => [k(L(R(m)("showroom.owners.add_child")), 1)]),
										_: 1
									}, 8, ["loading"])
								], 32),
								R(I).error("parent") ? (M(), D("small", Ue, L(R(I).error("parent")), 1)) : E("", !0)
							])]),
							_: 1
						}), A(ue, {
							assets: R(J).assets ?? [],
							owner: R(J).code,
							onChanged: R(q)
						}, null, 8, [
							"assets",
							"owner",
							"onChanged"
						])])
					])) : E("", !0)]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				])]),
				_: 1
			}, 8, ["record"])]);
		};
	}
});
export { K as default };
