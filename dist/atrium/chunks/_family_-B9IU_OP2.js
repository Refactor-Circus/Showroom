import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogState-Bkly-MlG.js";
import { C as n, D as r, O as i, T as a, a as o, f as s, k as c, r as ee, u as te } from "./catalog-DCoO3A8a.js";
import { s as l } from "./values-B4RtDnRM.js";
import { AtriumIntegrationScreen as ne, navigateTo as u } from "@refactor-circus/atrium";
import { UBadge as re, UButton as ie, UCard as ae, UFormField as oe, UInput as se } from "@refactor-circus/atrium/ui";
import { Fragment as d, computed as f, createCommentVNode as p, createElementBlock as m, createElementVNode as h, createTextVNode as g, createVNode as _, defineComponent as ce, openBlock as v, reactive as y, ref as le, renderList as b, resolveComponent as ue, toDisplayString as x, unref as S, vModelCheckbox as C, vModelSelect as w, vModelText as T, watch as de, withCtx as E, withDirectives as D, withModifiers as O } from "vue";
import { useI18n as fe } from "vue-i18n";
import { useRoute as pe } from "vue-router";
// src/pages/families/[family].vue?vue&type=script&setup=true&lang.ts
var me = {
	class: "space-y-4",
	"data-testid": "showroom-family"
}, k = {
	key: 0,
	class: "space-y-5"
}, A = { class: "flex flex-wrap items-start justify-between gap-3" }, j = {
	class: "text-xl font-semibold",
	"data-testid": "showroom-family-title"
}, M = { class: "font-mono text-sm text-muted" }, N = { class: "font-semibold" }, P = { class: "flex flex-wrap items-end gap-3" }, F = { value: "" }, I = ["value"], L = { class: "font-semibold" }, R = { class: "space-y-4" }, z = {
	key: 0,
	class: "text-sm text-muted"
}, he = {
	key: 1,
	class: "w-full text-left text-sm"
}, ge = { class: "text-muted" }, _e = { class: "py-1 pr-3 font-medium" }, ve = { class: "py-1 pr-3 font-medium" }, ye = { class: "py-1 pr-3 font-medium" }, be = { class: "py-1 pr-3 font-medium" }, xe = { class: "py-1 pr-3 font-medium" }, Se = { class: "py-1 pr-3 font-medium" }, Ce = { class: "py-1 font-medium" }, we = { class: "divide-y divide-default" }, Te = ["data-testid"], Ee = { class: "py-1 pr-3" }, De = { class: "py-1 pr-3" }, Oe = { class: "py-1 pr-3" }, ke = { class: "py-1 pr-3" }, Ae = ["onUpdate:modelValue", "data-testid"], je = { class: "py-1 pr-3" }, Me = [
	"onUpdate:modelValue",
	"placeholder",
	"data-testid"
], Ne = { class: "py-1 pr-3" }, Pe = ["onUpdate:modelValue", "data-testid"], Fe = { class: "py-1" }, Ie = ["onUpdate:modelValue", "data-testid"], B = {
	key: 2,
	class: "text-xs text-error"
}, Le = { class: "flex flex-wrap items-end gap-3" }, Re = { value: "" }, ze = ["value"], Be = { class: "flex h-9 items-center gap-2 text-sm" }, Ve = { class: "font-semibold" }, He = { class: "space-y-4" }, Ue = {
	key: 0,
	class: "flex flex-wrap gap-2"
}, We = { class: "px-1 text-sm font-medium" }, Ge = { class: "grid gap-3 sm:grid-cols-2" }, Ke = { class: "mb-1 text-xs font-medium uppercase text-muted" }, qe = { class: "flex flex-wrap gap-3" }, Je = [
	"checked",
	"data-testid",
	"onChange"
], Ye = { class: "font-mono text-xs" }, Xe = {
	key: 0,
	class: "text-xs text-error"
}, V = /* @__PURE__ */ ce({
	__name: "[family]",
	setup(ce) {
		let { t: V, locale: H } = fe(), Ze = pe(), U = i(), W = i(), G = f(() => String(Ze.params.family)), { client: K, data: q, pending: Qe, failed: $e, status: et, reload: J } = c(async (e) => ({
			family: n(await e.GET("/families/{family}", { params: { path: { family: G.value } } })).data,
			variants: n(await e.GET("/family-variants", { params: { query: {
				family: G.value,
				per_page: 100
			} } })).data
		}), G), Y = f(() => q.value?.family ?? null), X = f(() => Y.value?.attributes ?? []), tt = l(() => []), nt = f(() => [...tt.attributes.value.values()].filter((e) => !X.value.some((t) => t.attribute === e.code)).sort((e, t) => e.code.localeCompare(t.code))), rt = f(() => X.value.filter((e) => e.type === "text")), Z = y({
			label: "",
			label_attribute: "",
			sort_order: "0",
			add_attribute: "",
			add_required: !1
		}), Q = le([]), $ = y({
			code: "",
			levels: [{
				axes: [],
				attributes: []
			}, {
				axes: [],
				attributes: []
			}]
		});
		de(q, () => {
			let e = Y.value;
			e && (Z.label = e.labels[H.value] ?? "", Z.label_attribute = e.label_attribute ?? "", Z.sort_order = String(e.sort_order), Z.add_attribute = "", Z.add_required = !1, Q.value = (e.attributes ?? []).map((e) => ({
				attribute: e.attribute,
				type: e.type,
				group: e.group ?? null,
				is_required: e.is_required,
				required_channels: (e.required_channels ?? []).join(", "),
				sort_order: String(e.sort_order),
				remove: !1
			})));
		}, { immediate: !0 });
		async function it() {
			let e = Y.value;
			if (!K || !e) return;
			let t = Q.value.filter((e) => !e.remove).map((e) => {
				let t = a(e.required_channels);
				return {
					attribute: e.attribute,
					is_required: e.is_required,
					required_channels: t.length > 0 ? t : null,
					sort_order: Number(e.sort_order) || 0
				};
			}), i = Z.add_attribute ? [...t, {
				attribute: Z.add_attribute,
				is_required: Z.add_required,
				sort_order: t.length
			}] : t, s = {
				labels: {
					...e.labels,
					[H.value]: o(Z.label)
				},
				label_attribute: Z.label_attribute || null,
				sort_order: Number(Z.sort_order) || 0,
				attributes: i
			};
			await U.run(async () => n(await K.PATCH("/families/{family}", {
				params: { path: { family: G.value } },
				body: r(s)
			}))) && await J();
		}
		async function at() {
			K && window.confirm(V("showroom.catalog.families.confirm_delete")) && await U.run(async () => n(await K.DELETE("/families/{family}", { params: { path: { family: G.value } } }))) && await u("/showroom/families");
		}
		function ot(e, t, n, r) {
			e[t] = r ? [...e[t].filter((e) => e !== n), n] : e[t].filter((e) => e !== n);
		}
		function st(e) {
			return e.target.checked;
		}
		async function ct() {
			if (!K) return;
			let e = $.levels.filter((e, t) => t === 0 || e.axes.length > 0 || e.attributes.length > 0).map((e) => ({
				axes: [...e.axes],
				attributes: [...e.attributes]
			}));
			await W.run(async () => n(await K.POST("/family-variants", { body: r({
				code: $.code.trim(),
				family: G.value,
				levels: e
			}) }))) && ($.code = "", $.levels = [{
				axes: [],
				attributes: []
			}, {
				axes: [],
				attributes: []
			}], await J());
		}
		return (n, r) => {
			let i = ie, a = se, o = oe, c = ae, l = ue("RouterLink"), u = re, f = ne;
			return v(), m("div", me, [_(f, {
				"screen-key": "showroom.family",
				record: S(G)
			}, {
				default: E(() => [_(t, {
					ready: !S(Qe) && S(Y) !== null,
					failed: S($e),
					status: S(et)
				}, {
					default: E(() => [S(Y) ? (v(), m("div", k, [
						h("div", A, [h("div", null, [h("h1", j, x(S(s)(S(Y).labels, S(H), S(Y).code)), 1), h("p", M, x(S(Y).code), 1)]), _(i, {
							color: "error",
							variant: "outline",
							icon: "i-lucide-trash-2",
							loading: S(U).busy.value,
							"data-testid": "showroom-family-delete",
							onClick: at
						}, {
							default: E(() => [g(x(S(V)("showroom.catalog.delete")), 1)]),
							_: 1
						}, 8, ["loading"])]),
						h("form", {
							class: "space-y-5",
							"data-testid": "showroom-family-form",
							onSubmit: O(it, ["prevent"])
						}, [
							_(e, { action: S(U) }, null, 8, ["action"]),
							_(c, null, {
								header: E(() => [h("h2", N, x(S(V)("showroom.catalog.products.details")), 1)]),
								default: E(() => [h("div", P, [
									_(o, {
										label: S(V)("showroom.catalog.fields.label_in", { locale: S(H) }),
										error: S(U).error("labels")
									}, {
										default: E(() => [_(a, {
											modelValue: S(Z).label,
											"onUpdate:modelValue": r[0] ||= (e) => S(Z).label = e,
											class: "w-64",
											"data-testid": "showroom-family-label"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									_(o, {
										label: S(V)("showroom.catalog.families.label_attribute"),
										hint: S(V)("showroom.catalog.families.label_attribute_hint"),
										error: S(U).error("label_attribute")
									}, {
										default: E(() => [D(h("select", {
											"onUpdate:modelValue": r[1] ||= (e) => S(Z).label_attribute = e,
											class: "w-64 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
											"data-testid": "showroom-family-label-attribute"
										}, [h("option", F, x(S(V)("showroom.catalog.families.no_label_attribute")), 1), (v(!0), m(d, null, b(S(rt), (e) => (v(), m("option", {
											key: e.attribute,
											value: e.attribute
										}, x(e.attribute), 9, I))), 128))], 512), [[w, S(Z).label_attribute]])]),
										_: 1
									}, 8, [
										"label",
										"hint",
										"error"
									]),
									_(o, {
										label: S(V)("showroom.catalog.fields.sort_order"),
										error: S(U).error("sort_order")
									}, {
										default: E(() => [_(a, {
											modelValue: S(Z).sort_order,
											"onUpdate:modelValue": r[2] ||= (e) => S(Z).sort_order = e,
											type: "number",
											min: "0",
											class: "w-28",
											"data-testid": "showroom-family-sort"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"])
								])]),
								_: 1
							}),
							_(c, { "data-testid": "showroom-family-attributes" }, {
								header: E(() => [h("h2", L, x(S(V)("showroom.catalog.families.members")), 1)]),
								default: E(() => [h("div", R, [
									S(Q).length === 0 ? (v(), m("p", z, x(S(V)("showroom.catalog.families.no_attributes")), 1)) : (v(), m("table", he, [h("thead", ge, [h("tr", null, [
										h("th", _e, x(S(V)("showroom.catalog.fields.code")), 1),
										h("th", ve, x(S(V)("showroom.catalog.fields.type")), 1),
										h("th", ye, x(S(V)("showroom.catalog.fields.group")), 1),
										h("th", be, x(S(V)("showroom.catalog.families.required")), 1),
										h("th", xe, x(S(V)("showroom.catalog.families.required_channels")), 1),
										h("th", Se, x(S(V)("showroom.catalog.fields.sort_order")), 1),
										h("th", Ce, x(S(V)("showroom.catalog.remove")), 1)
									])]), h("tbody", we, [(v(!0), m(d, null, b(S(Q), (e) => (v(), m("tr", {
										key: e.attribute,
										"data-testid": `showroom-family-attribute-${e.attribute}`
									}, [
										h("td", Ee, [_(l, {
											to: S(ee)(e.attribute),
											class: "font-mono hover:underline"
										}, {
											default: E(() => [g(x(e.attribute), 1)]),
											_: 2
										}, 1032, ["to"])]),
										h("td", De, [_(u, {
											color: "neutral",
											variant: "subtle"
										}, {
											default: E(() => [g(x(e.type), 1)]),
											_: 2
										}, 1024)]),
										h("td", Oe, x(e.group ?? S(V)("showroom.catalog.none")), 1),
										h("td", ke, [D(h("input", {
											"onUpdate:modelValue": (t) => e.is_required = t,
											type: "checkbox",
											"data-testid": `showroom-family-required-${e.attribute}`
										}, null, 8, Ae), [[C, e.is_required]])]),
										h("td", je, [D(h("input", {
											"onUpdate:modelValue": (t) => e.required_channels = t,
											class: "w-40 rounded-md border border-default bg-default px-2 py-1 text-sm",
											placeholder: S(V)("showroom.catalog.families.required_channels_hint"),
											"data-testid": `showroom-family-channels-${e.attribute}`
										}, null, 8, Me), [[T, e.required_channels]])]),
										h("td", Ne, [D(h("input", {
											"onUpdate:modelValue": (t) => e.sort_order = t,
											type: "number",
											min: "0",
											class: "w-20 rounded-md border border-default bg-default px-2 py-1 text-sm",
											"data-testid": `showroom-family-sort-${e.attribute}`
										}, null, 8, Pe), [[T, e.sort_order]])]),
										h("td", Fe, [D(h("input", {
											"onUpdate:modelValue": (t) => e.remove = t,
											type: "checkbox",
											"data-testid": `showroom-family-remove-${e.attribute}`
										}, null, 8, Ie), [[C, e.remove]])])
									], 8, Te))), 128))])])),
									S(U).error("attributes") ? (v(), m("small", B, x(S(U).error("attributes")), 1)) : p("", !0),
									h("div", Le, [_(o, { label: S(V)("showroom.catalog.values.add_attribute") }, {
										default: E(() => [D(h("select", {
											"onUpdate:modelValue": r[3] ||= (e) => S(Z).add_attribute = e,
											class: "w-72 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
											"data-testid": "showroom-family-add-attribute"
										}, [h("option", Re, x(S(V)("showroom.catalog.values.choose_attribute")), 1), (v(!0), m(d, null, b(S(nt), (e) => (v(), m("option", {
											key: e.code,
											value: e.code
										}, x(e.code) + " (" + x(e.type) + ")", 9, ze))), 128))], 512), [[w, S(Z).add_attribute]])]),
										_: 1
									}, 8, ["label"]), h("label", Be, [D(h("input", {
										"onUpdate:modelValue": r[4] ||= (e) => S(Z).add_required = e,
										type: "checkbox",
										"data-testid": "showroom-family-add-required"
									}, null, 512), [[C, S(Z).add_required]]), g(" " + x(S(V)("showroom.catalog.families.required")), 1)])])
								])]),
								_: 1
							}),
							h("div", null, [_(i, {
								type: "submit",
								icon: "i-lucide-check",
								loading: S(U).busy.value,
								"data-testid": "showroom-family-save"
							}, {
								default: E(() => [g(x(S(V)("showroom.catalog.save")), 1)]),
								_: 1
							}, 8, ["loading"])])
						], 32),
						_(c, { "data-testid": "showroom-family-variants" }, {
							header: E(() => [h("h2", Ve, x(S(V)("showroom.catalog.families.variants")), 1)]),
							default: E(() => [h("div", He, [
								(S(q)?.variants ?? []).length > 0 ? (v(), m("ul", Ue, [(v(!0), m(d, null, b(S(q)?.variants ?? [], (e) => (v(), m("li", { key: e.code }, [_(l, {
									to: S(te)(e.code),
									class: "font-mono text-sm hover:underline",
									"data-testid": `showroom-family-variant-${e.code}`
								}, {
									default: E(() => [g(x(e.code), 1)]),
									_: 2
								}, 1032, ["to", "data-testid"])]))), 128))])) : p("", !0),
								_(e, { action: S(W) }, null, 8, ["action"]),
								h("form", {
									class: "flex flex-col gap-4",
									"data-testid": "showroom-family-variant-create-form",
									onSubmit: O(ct, ["prevent"])
								}, [
									_(o, {
										label: S(V)("showroom.catalog.fields.code"),
										error: S(W).error("code"),
										required: ""
									}, {
										default: E(() => [_(a, {
											modelValue: S($).code,
											"onUpdate:modelValue": r[5] ||= (e) => S($).code = e,
											required: "",
											class: "w-64",
											"data-testid": "showroom-family-variant-code"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									(v(!0), m(d, null, b(S($).levels, (e, t) => (v(), m("fieldset", {
										key: t,
										class: "rounded-md border border-default p-3"
									}, [
										h("legend", We, x(S(V)("showroom.catalog.families.level", { level: t + 1 })), 1),
										h("div", Ge, [(v(), m(d, null, b(["axes", "attributes"], (n) => h("div", { key: n }, [h("p", Ke, x(S(V)(`showroom.catalog.families.${n}`)), 1), h("div", qe, [(v(!0), m(d, null, b(S(X), (r) => (v(), m("label", {
											key: r.attribute,
											class: "flex items-center gap-1.5 text-sm"
										}, [h("input", {
											type: "checkbox",
											checked: e[n].includes(r.attribute),
											"data-testid": `showroom-level-${t}-${n}-${r.attribute}`,
											onChange: (t) => ot(e, n, r.attribute, st(t))
										}, null, 40, Je), h("span", Ye, x(r.attribute), 1)]))), 128))])])), 64))]),
										S(W).error(`levels.${t}`) ? (v(), m("small", Xe, x(S(W).error(`levels.${t}`)), 1)) : p("", !0)
									]))), 128)),
									h("div", null, [_(i, {
										type: "submit",
										icon: "i-lucide-plus",
										loading: S(W).busy.value,
										"data-testid": "showroom-family-variant-create"
									}, {
										default: E(() => [g(x(S(V)("showroom.catalog.families.create_variant")), 1)]),
										_: 1
									}, 8, ["loading"])])
								], 32)
							])]),
							_: 1
						})
					])) : p("", !0)]),
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
export { V as default };
